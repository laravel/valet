<?php

/**
 * Define the user's "~/.valet" path.
 */

define('VALET_HOME_PATH', posix_getpwuid(fileowner(__FILE__))['dir'].'/.valet');
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
 * @param $domain string Domain to filter
 *
 * @return string Filtered domain (without wildcard dns feature (xip.io/nip.io))
 */
function valet_support_wildcard_dns($domain)
{
    if (in_array(substr($domain, -7), ['.xip.io', '.nip.io'])) {
        // support only ip v4 for now
        $domainPart = explode('.', $domain);
        if (count($domainPart) > 6) {
            $domain = implode('.', array_reverse(array_slice(array_reverse($domainPart), 6)));
        }
    }

    return $domain;
}

/**
 * Detect the sitename with given domain
 * @param  $domain string domain to use as suffix
 * @return string The discovered sitename
 */
function detect_sitename($domain)
{
    $siteName = basename(
        // Filter host to support xip.io feature
        valet_support_wildcard_dns($_SERVER['HTTP_HOST']),
        '.'.$domain
    );

    if (strpos($siteName, 'www.') === 0) {
        $siteName = substr($siteName, 4);
    }

    return $siteName;
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
$uri = urldecode(
    explode("?", $_SERVER['REQUEST_URI'])[0]
);

/**
 * Determine the fully qualified path to the site.
 */
$valetSitePath = null;

foreach ($valetConfig['paths'] as $path) {
    if (is_array($path)) {
        $siteName = detect_sitename($path['domain']);
        $path = $path['path'];
    } else {
        $siteName = detect_sitename($valetConfig['domain']);
    }
    $domain = array_slice(explode('.', $siteName), -1)[0];

    if (is_dir($path.'/'.$siteName)) {
        $valetSitePath = $path.'/'.$siteName;
        break;
    }

    if (is_dir($path.'/'.$domain)) {
        $valetSitePath = $path.'/'.$domain;
        break;
    }
}

if (is_null($valetSitePath)) {
    show_valet_404();
}

$valetSitePath = realpath($valetSitePath);

/**
 * Find the appropriate Valet driver for the request.
 */
$valetDriver = null;

require __DIR__.'/cli/drivers/require.php';

$valetDriver = ValetDriver::assign($valetSitePath, $siteName, $uri);

if (! $valetDriver) {
    show_valet_404();
}

/**
 * ngrok uses the X-Original-Host to store the forwarded hostname.
 */
if (isset($_SERVER['HTTP_X_ORIGINAL_HOST']) && !isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $_SERVER['HTTP_X_FORWARDED_HOST'] = $_SERVER['HTTP_X_ORIGINAL_HOST'];
}

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
    show_valet_404();
}

chdir(dirname($frontControllerPath));

require $frontControllerPath;
