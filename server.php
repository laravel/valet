<?php

require_once './cli/includes/require-drivers.php';
require_once './cli/includes/require-legacy-files.php';

use Valet\Drivers\ValetDriver;

/**
 * Define the user's "~/.config/valet" path.
 */
define('VALET_HOME_PATH', posix_getpwuid(fileowner(__FILE__))['dir'].'/.config/valet');
define('VALET_STATIC_PREFIX', '41c270e4-5535-4daa-b23e-c269744c2f45');

/**
 * Show the Valet 404 "Not Found" page.
 */
function show_valet_404()
{
    http_response_code(404);
    require __DIR__.'/cli/templates/404.html';
    exit;
}

/**
 * Show directory listing or 404 if directory doesn't exist.
 */
function show_directory_listing($valetSitePath, $uri)
{
    $is_root = ($uri == '/');
    $directory = ($is_root) ? $valetSitePath : $valetSitePath.$uri;

    if (! file_exists($directory)) {
        show_valet_404();
    }

    // Sort directories at the top
    $paths = glob("$directory/*");
    usort($paths, function ($a, $b) {
        return (is_dir($a) == is_dir($b)) ? strnatcasecmp($a, $b) : (is_dir($a) ? -1 : 1);
    });

    // Output the HTML for the directory listing
    echo "<h1>Index of $uri</h1>";
    echo '<hr>';
    echo implode("<br>\n", array_map(function ($path) use ($uri, $is_root) {
        $file = basename($path);

        return ($is_root) ? "<a href='/$file'>/$file</a>" : "<a href='$uri/$file'>$uri/$file/</a>";
    }, $paths));

    exit;
}

/**
 * You may use wildcard DNS provider nip.io as a tool for testing your site via an IP address.
 * It's simple to use: First determine the IP address of your local computer (like 192.168.0.10).
 * Then simply use http://project.your-ip.nip.io - ie: http://laravel.192.168.0.10.nip.io.
 */
function valet_support_wildcard_dns($domain, $config)
{
    $services = [
        '.*.*.*.*.nip.io',
        '-*-*-*-*.nip.io',
    ];

    if (isset($config['tunnel_services'])) {
        $services = array_merge($services, (array) $config['tunnel_services']);
    }

    $patterns = [];
    foreach ($services as $service) {
        $pattern = preg_quote($service, '#');
        $pattern = str_replace('\*', '.*', $pattern);
        $patterns[] = '(.*)'.$pattern;
    }

    $pattern = implode('|', $patterns);

    if (preg_match('#(?:'.$pattern.')\z#u', $domain, $matches)) {
        $domain = array_pop($matches);
    }

    if (strpos($domain, ':') !== false) {
        $domain = explode(':', $domain)[0];
    }

    return $domain;
}

/**
 * @param  array  $config  Valet configuration array
 * @return string|null If set, default site path for uncaught urls
 * */
function valet_default_site_path($config)
{
    if (isset($config['default']) && is_string($config['default']) && is_dir($config['default'])) {
        return $config['default'];
    }

    return null;
}

/**
 * Load the Valet configuration.
 */
$valetConfig = json_decode(
    file_get_contents(VALET_HOME_PATH.'/config.json'), true
);

/**
 * Parse the URI and site / host for the incoming request.
 */
$uri = rawurldecode(
    explode('?', $_SERVER['REQUEST_URI'])[0]
);

$siteName = basename(
    // Filter host to support wildcard dns feature
    valet_support_wildcard_dns($_SERVER['HTTP_HOST'], $valetConfig),
    '.'.$valetConfig['tld']
);

if (strpos($siteName, 'www.') === 0) {
    $siteName = substr($siteName, 4);
}

/**
 * Determine the fully qualified path to the site.
 * Inspects registered path directories, case-sensitive.
 */
function get_valet_site_path($valetConfig, $siteName, $domain)
{
    $valetSitePath = null;

    foreach ($valetConfig['paths'] as $path) {
        $handle = opendir($path);

        if ($handle === false) {
            continue;
        }

        $dirs = [];

        while (false !== ($file = readdir($handle))) {
            if (is_dir($path.'/'.$file) && ! in_array($file, ['.', '..'])) {
                $dirs[] = $file;
            }
        }

        closedir($handle);

        // Note: strtolower used below because Nginx only tells us lowercase names
        foreach ($dirs as $dir) {
            if (strtolower($dir) === $siteName) {
                // early return when exact match for linked subdomain
                return $path.'/'.$dir;
            }

            if (strtolower($dir) === $domain) {
                // no early return here because the foreach may still have some subdomains to process with higher priority
                $valetSitePath = $path.'/'.$dir;
            }
        }

        if ($valetSitePath) {
            return $valetSitePath;
        }
    }
}

$domain = array_slice(explode('.', $siteName), -1)[0];
$valetSitePath = get_valet_site_path($valetConfig, $siteName, $domain);

if (is_null($valetSitePath) && is_null($valetSitePath = valet_default_site_path($valetConfig))) {
    show_valet_404();
}

$valetSitePath = realpath($valetSitePath);

/**
 * Find the appropriate Valet driver for the request.
 */
$valetDriver = null;

$valetDriver = ValetDriver::assign($valetSitePath, $siteName, $uri);

if (! $valetDriver) {
    show_valet_404();
}

/**
 * ngrok uses the X-Original-Host to store the forwarded hostname.
 */
if (isset($_SERVER['HTTP_X_ORIGINAL_HOST']) && ! isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $_SERVER['HTTP_X_FORWARDED_HOST'] = $_SERVER['HTTP_X_ORIGINAL_HOST'];
}

/**
 * Attempt to load server environment variables.
 */
$valetDriver->loadServerEnvironmentVariables(
    $valetSitePath, $siteName
);

/**
 * Allow driver to mutate incoming URL.
 */
$uri = $valetDriver->mutateUri($uri);

/**
 * Determine if the incoming request is for a static file.
 */
$isPhpFile = pathinfo($uri, PATHINFO_EXTENSION) === 'php';

if ($uri !== '/' && ! $isPhpFile && $staticFilePath = $valetDriver->isStaticFile($valetSitePath, $siteName, $uri)) {
    return $valetDriver->serveStaticFile($staticFilePath, $valetSitePath, $siteName, $uri);
}

/**
 * Attempt to dispatch to a front controller.
 */
$frontControllerPath = $valetDriver->frontControllerPath(
    $valetSitePath, $siteName, $uri
);

if (! $frontControllerPath) {
    if (isset($valetConfig['directory-listing']) && $valetConfig['directory-listing'] == 'on') {
        show_directory_listing($valetSitePath, $uri);
    }

    show_valet_404();
}

chdir(dirname($frontControllerPath));

require $frontControllerPath;
