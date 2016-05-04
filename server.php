<?php

/**
 * Load the Valet configuration.
 */
$GLOBALS['VALET'] = json_decode(
    file_get_contents('/Users/'.posix_getpwuid(fileowner(__FILE__))['name'].'/.valet/config.json'), true
);

/**
 * Parse the URI and host for the incoming request.
 */
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

$site = basename(
    $_SERVER['HTTP_HOST'],
    '.'.$GLOBALS['VALET']['domain'] ?? 'dev'
);

/**
 * Determine if the given URI is a static file.
 *
 * @param  string  $site
 * @param  string  $uri
 * @return bool
 */
function is_static_file($site, $uri)
{
    if ($uri === '/') {
        return false;
    }

    foreach ($GLOBALS['VALET']['paths'] as $path) {
        if (file_exists($path.'/'.$site.'/public'.$uri)) {
            return true;
        }
    }
}

/**
 * Serve a static file by URI.
 *
 * @param  string  $site
 * @param  string  $uri
 * @return void
 */
function serve_file($site, $uri)
{
    $mimes = require(__DIR__.'/mimes.php');

    header('Content-Type: '.$mimes[pathinfo($uri)['extension']]);

    foreach ($GLOBALS['VALET']['paths'] as $path) {
        if (file_exists($path.'/'.$site.'/public'.$uri)) {
            readfile($path.'/'.$site.'/public'.$uri);

            return;
        }
    }
}

/**
 * Dispatch to the given site's Laravel installation.
 */
function dispatch($site)
{
    foreach ($GLOBALS['VALET']['paths'] as $path) {
        if (file_exists($indexPath = $path.'/'.$site.'/public/index.php')) {
            posix_setuid(fileowner($indexPath));

            return require_once $indexPath;
        }
    }

    http_response_code(404);

    require __DIR__.'/404.html';
}

/**
 * Serve the request.
 */
is_static_file($site, $uri) ? serve_file($site, $uri) : dispatch($site);
