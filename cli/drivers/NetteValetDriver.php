<?php

class NetteValetDriver extends ValetDriver
{
    /**
     * Determine if driver serves the request.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     * @return bool
     */
    public function serves($sitePath, $siteName, $uri)
    {
        return file_exists($sitePath.'/www/index.php') &&
            file_exists($sitePath.'/app/Bootstrap.php') &&
            $this->checkNanoConfigFiles($sitePath);
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     * @return false|string
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        if (file_exists($staticFilePath = $sitePath.'/www'.$uri)
            && is_file($staticFilePath)) {
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
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        return $sitePath.'/www/index.php';
    }

    /**
     * Check if .neon config files are present.
     *
     * @param $sitePath
     * @return bool
     */
    private function checkNanoConfigFiles($sitePath)
    {
        $path = $sitePath.'/config/';

        $files = glob($path.'*.neon');

        return !empty($files);
    }
}
