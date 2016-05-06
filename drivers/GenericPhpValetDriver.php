<?php

class GenericPhpValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return void
     */
    public function serves($sitePath, $siteName, $uri)
    {
        return file_exists($sitePath.'/public/index.php') ||
               file_exists($sitePath.'/index.php');
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
        if (file_exists($staticFilePath = $sitePath.'/public'.$uri)) {
            return $staticFilePath;
        } elseif (file_exists($staticFilePath = $sitePath.$uri)) {
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
        if (file_exists($frontControllerPath = $sitePath.'/public/index.php')) {
            $_SERVER['SCRIPT_FILENAME'] = $sitePath.'/public/index.php';
            $_SERVER['SCRIPT_NAME'] = '/index.php';
        } elseif (file_exists($frontControllerPath = $sitePath.'/index.php')) {
            $_SERVER['SCRIPT_FILENAME'] = $sitePath.'/index.php';
            $_SERVER['SCRIPT_NAME'] = '/index.php';
        }

        return $frontControllerPath;
    }
}
