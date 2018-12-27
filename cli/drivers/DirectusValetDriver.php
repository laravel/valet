<?php

class DirectusValetDriver extends ValetDriver
{
    public function parseUrl($uri)
    {
        $URL = parse_url($uri);
        $URL['parts'] = explode('/', trim($URL['path'], '/'));
        $URL['first'] = reset($URL['parts']);
        $URL['last'] = end($URL['parts']);
        return $URL;
    }
    /**
     * Determine if the driver serves the request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return bool
     */
    public function serves($sitePath, $siteName, $uri)
    {
        return file_exists($sitePath.'/bin/directus');
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        if ($uri === '/admin') {
            return false;
        }

        if (file_exists($staticFilePath = $sitePath.'/public/admin'.$uri)) {
            return $staticFilePath;
        }

        if (file_exists($staticFilePath = $sitePath.'/public'.$uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        $url = $this->parseUrl($uri);

        if ($url['first'] === 'admin') {
            return $sitePath.'/public/admin/index.html';
        }

        if ($url['first'] === 'api') {
            if (strpos($url['path'], 'api/extensions') !== false) {
                return $sitePath.'/public/api.php?run_extension=' . implode('/', array_slice($url['parts'], 2));
            } else {
                return $sitePath.'/public/api.php?run_api_router=1';
            }
        }

        return $sitePath.'/public/index.php';
    }
}
