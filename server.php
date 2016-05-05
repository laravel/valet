<?php

/**
 * Define the user's "~/.valet" path.
 */
define('VALET_HOME_PATH', '/Users/'.posix_getpwuid(fileowner(__FILE__))['name'].'/.valet');

/**
 * De-escalate root privileges down to Valet directory owner.
 */
posix_setuid(fileowner(VALET_HOME_PATH.'/config.json'));

/**
 * Show the Valet 404 "Not Found" page.
 */
function show_valet_404()
{
    http_response_code(404);
    require __DIR__.'/404.html';
    exit;
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
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

$siteName = basename(
    $_SERVER['HTTP_HOST'],
    '.'.$valetConfig['domain']
);

/**
 * Determine the fully qualified path to the site.
 */
$valetSitePath = null;

foreach ($valetConfig['paths'] as $path) {
    if (is_dir($path.'/'.$siteName)) {
        $valetSitePath = $path.'/'.$siteName;

        break;
    }
}

if (is_null($valetSitePath)) {
    show_valet_404();
}

/**
 * Find the appropriate Valet driver for the request.
 */
$valetDriver = null;

require_once __DIR__.'/drivers/ValetDriver.php';
require_once __DIR__.'/drivers/StatamicValetDriver.php';
require_once __DIR__.'/drivers/LaravelValetDriver.php';
require_once __DIR__.'/drivers/StaticValetDriver.php';

$valetDriver = ValetDriver::assign($valetSitePath, $siteName, $uri);

if (! $valetDriver) {
    show_valet_404();
}

/**
 * Dispatch the request.
 */
if ($uri !== '/' && $staticFilePath = $valetDriver->isStaticFile($valetSitePath, $siteName, $uri)) {
    return $valetDriver->serveStaticFile($staticFilePath, $valetSitePath, $siteName, $uri);
}

$frontControllerPath = $valetDriver->frontControllerPath(
    $valetSitePath, $siteName, $uri
);

require $frontControllerPath;
