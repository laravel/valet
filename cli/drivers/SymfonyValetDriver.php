<?php

class SymfonyValetDriver extends ValetDriver
{
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
        return (file_exists($sitePath.'/web/app_dev.php') || file_exists($sitePath.'/web/app.php')) &&
               (file_exists($sitePath.'/app/AppKernel.php')) || (file_exists($sitePath.'/web/index.php')) &&
               (file_exists($sitePath.'/src/Kernel.php'))
            ;
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
        if ($this->isActualFile($staticFilePath = $sitePath.'/web/'.$uri)) {
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
        if (file_exists($frontControllerPath = $sitePath.'/web/app_dev.php')) {
            return $frontControllerPath;
        } elseif (file_exists($frontControllerPath = $sitePath.'/web/app.php')) {
            return $frontControllerPath;
        } elseif (file_exists($frontControllerPath = $sitePath.'/web/index.php')) {
            return $frontControllerPath;
        }
    }
}
