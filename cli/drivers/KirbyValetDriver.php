<?php

class KirbyValetDriver extends ValetDriver
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
        return is_dir($sitePath.'/kirby');
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
       if ($this->isActualFile($staticFilePath = $sitePath.$uri)) {
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
        // Needed to force Kirby to use *.dev to generate its URLs...
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];

        if (preg_match('/^\/panel/', $uri)) {
            $_SERVER['SCRIPT_NAME'] = '/panel/index.php';

            return $sitePath.'/panel/index.php';
        }

        if (file_exists($indexPath = $sitePath.'/index.php')) {
            $_SERVER['SCRIPT_NAME'] = '/index.php';

            return $indexPath;
        }
    }
}
