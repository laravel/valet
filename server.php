<?php


// FIX PHP PATH_INFO

$originalRequestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Needs patch
if (substr($path, -strlen($_SERVER['PATH_INFO'])) == $_SERVER['PATH_INFO']) {
    // Cut away PHP PATH_INFO from REQUEST_URI to find the correct file later
    $_SERVER['REQUEST_URI'] =
        substr($path, 0, -strlen($_SERVER['PATH_INFO'])) // Remove PATH_INFO: page.php/remove/additional/path
        . substr($_SERVER['REQUEST_URI'], strlen($path)); // Restore GET parameters '?param=what&ever=floats'
}
unset($path); // Don't leave global garbage around

// END FIX PHP PATH_INFO

require_once './cli/includes/require-drivers.php';
require_once './cli/Valet/Server.php';

use Valet\Drivers\ValetDriver;
use Valet\Server;

/**
 * Define the user's "~/.config/valet" path.
 */
defined('VALET_HOME_PATH') or define('VALET_HOME_PATH', posix_getpwuid(fileowner(__FILE__))['dir'].'/.config/valet');
defined('VALET_STATIC_PREFIX') or define('VALET_STATIC_PREFIX', '41c270e4-5535-4daa-b23e-c269744c2f45');

/**
 * Load the Valet configuration.
 */
$valetConfig = json_decode(
    file_get_contents(VALET_HOME_PATH.'/config.json'), true
);

/**
 * If the HTTP_HOST is an IP address, check the start of the REQUEST_URI for a
 * valid hostname, extract and use it as the effective HTTP_HOST in place
 * of the IP. It enables the use of Valet in a local network.
 */
if (Server::hostIsIpAddress($_SERVER['HTTP_HOST'])) {
    $uriForIpAddressExtraction = ltrim($_SERVER['REQUEST_URI'], '/');

    if ($host = Server::valetSiteFromIpAddressUri($uriForIpAddressExtraction, $valetConfig['tld'])) {
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['REQUEST_URI'] = str_replace($host, '', $uriForIpAddressExtraction);
    }
}

$server = new Server($valetConfig);

/**
 * Parse the URI and site / host for the incoming request.
 */
$uri = Server::uriFromRequestUri($_SERVER['REQUEST_URI']);
$siteName = $server->siteNameFromHttpHost($_SERVER['HTTP_HOST']);
$valetSitePath = $server->sitePath($siteName);

if (is_null($valetSitePath) && is_null($valetSitePath = $server->defaultSitePath())) {
    Server::show404();
}

$valetSitePath = realpath($valetSitePath);

/**
 * Find the appropriate Valet driver for the request.
 */
$valetDriver = ValetDriver::assign($valetSitePath, $siteName, $uri);

if (! $valetDriver) {
    Server::show404();
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
 * Allow for drivers to take pre-loading actions (e.g. setting server variables).
 */
$valetDriver->beforeLoading($valetSitePath, $siteName, $uri);

/**
 * Attempt to dispatch to a front controller.
 */
$frontControllerPath = $valetDriver->frontControllerPath(
    $valetSitePath, $siteName, $uri
);

if (! $frontControllerPath) {
    if (isset($valetConfig['directory-listing']) && $valetConfig['directory-listing'] == 'on') {
        Server::showDirectoryListing($valetSitePath, $uri);
    }

    Server::show404();
}

chdir(dirname($frontControllerPath));

// After the file is found recover the original REQUEST_URI
$_SERVER['REQUEST_URI'] = $originalRequestUri;
unset($originalRequestUri); // Don't leave useless globals around

require $frontControllerPath;
