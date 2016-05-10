<?php

class SinglePageApplicationValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     */
    public function serves($sitePath, $siteName, $uri)
    {
        return $this->driverDefinedInPackage($sitePath) ||
               $this->hasSpaDirectories($sitePath) &&
               $this->hasIndexHtmlFileAtRoot($sitePath);
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     *
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        if (file_exists($staticFilePath = $sitePath.'/dist'.$uri)) {
            return $staticFilePath;
        }

        if (file_exists($staticFilePath = $sitePath.$uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     *
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        if (is_dir($sitePath.'/dist')) {
            return $sitePath.'/dist/index.html';
        }

        return $sitePath.'/index.html';
    }

    /**
     * Check the app package.json file to see if valet drive is defined.
     *
     * @param string $sitePath
     * @return mixed
     */
    protected function driverDefinedInPackage($sitePath)
    {
        if (file_exists($packageJson = $sitePath.'/package.json')) {
            $packageJson = json_decode(file_get_contents($packageJson));

            if (isset($packageJson->valet_driver) && $packageJson->valet_driver == 'spa') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the app has common spa directories.
     *
     * @param string $sitePath
     * @return bool
     */
    protected function hasSpaDirectories($sitePath)
    {
        return file_exists($sitePath.'/package.json') &&
               file_exists($sitePath.'/node_modules');
    }

    /**
     * Check to see if the app has an index.html file in a common location.
     *
     * @param sring $sitePath
     * @return bool
     */
    protected function hasIndexHtmlFileAtRoot($sitePath)
    {
        return file_exists($sitePath.'/dist/index.html') ||
               file_exists($sitePath.'/index.html');
    }
}
