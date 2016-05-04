<?php

/**
 * Determine if the given URI is a static file.
 *
 * @param  string  $site
 * @param  string  $uri
 * @return bool
 */
function is_static_statamic_file($site, $uri)
{
    if ($uri === '/') {
        return false;
    }

    if (strpos($uri, '/site') === 0 && strpos($uri, '/site/themes') !== 0) {
        return false;
    }

    if (strpos($uri, '/local') === 0) {
        return false;
    }

    if (strpos($uri, '/statamic') === 0) {
        return false;
    }

    if (file_exists(VALET_SITE_PATH.'/'.$uri) ||
        file_exists(VALET_SITE_PATH.'/public/'.$uri)) {
        return true;
    }
}

/**
 * Serve a static file by URI.
 *
 * @param  string  $site
 * @param  string  $uri
 * @return void
 */
function serve_statamic_file($site, $uri)
{
    $mimes = require(__DIR__.'/mimes.php');

    header('Content-Type: '.$mimes[pathinfo($uri)['extension']]);

    if (file_exists($path = VALET_SITE_PATH.'/'.$uri) ||
        file_exists($path = VALET_SITE_PATH.'/public/'.$uri)) {
        readfile($path);

        return;
    }
}

/**
 * Serve the request for static assets.
 */
if (is_static_statamic_file($site, $uri)) {
    serve_statamic_file($site, $uri);
}

/**
 * Serve the request to the front controller.
 */
if (file_exists($indexPath = VALET_SITE_PATH.'/index.php') ||
    file_exists($indexPath = VALET_SITE_PATH.'/public/index.php')) {
    posix_setuid(fileowner($indexPath));

    return require_once $indexPath;
}

http_response_code(404);

require __DIR__.'/404.html';
