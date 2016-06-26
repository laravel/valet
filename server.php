<?php

/**
 * Define the user's "~/.valet" path.
 */

define('VALET_HOME_PATH', posix_getpwuid(fileowner(__FILE__))['dir'].'/.valet');

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
 * Parse the URI and determine domain for incoming request.
 */
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

$domain = substr(
    $_SERVER['HTTP_HOST'],
    strrpos($_SERVER['HTTP_HOST'], '.')+1
);

/**
 * Load the Valet configuration based on domain for incoming request.
 */
$valetConfig = json_decode(
    file_get_contents(VALET_HOME_PATH.'/config.json'), true
);

$valetConfig = @array_pop(array_filter($valetConfig['domains'], function($data) use ($domain) {
    return ($data['domain'] == $domain);
}));

/**
 * Parse the hostname and determine site name for incoming request.
 */
$siteName = basename(
    $_SERVER['HTTP_HOST'],
    '.'.$domain
);

if (strpos($siteName, 'www.') === 0) {
    $siteName = substr($siteName, 4);
}

/**
 * Determine the fully qualified path to the site.
 */
$valetSitePath = null;

if (array_key_exists('paths', $valetConfig)) {
    foreach ($valetConfig['paths'] as $path) {
        if (is_dir($path . '/' . $siteName)) {
            $valetSitePath = $path . '/' . $siteName;

            break;
        }
    }
}

if (is_null($valetSitePath)) {
    show_valet_404();
}

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
 * Overwrite the HTTP host for Ngrok.
 */
if (isset($_SERVER['HTTP_X_ORIGINAL_HOST'])) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_ORIGINAL_HOST'];
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
