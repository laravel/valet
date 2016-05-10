<?php

class BedrockValetDriver extends BasicValetDriver
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
        return file_exists($sitePath.'/web/app/mu-plugins/bedrock-autoloader.php')
            || (
                is_dir($sitePath.'/web/app/')
                && file_exists($sitePath.'/web/wp-config.php')
                && file_exists($sitePath.'/config/application.php')
            );
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
        $staticFilePath = $sitePath.'/web'.$uri;

        if (file_exists($staticFilePath) && ! is_dir($staticFilePath)) {
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
        $_SERVER['PHP_SELF'] = $uri;

        if (0 === strpos($uri, '/wp/')) {
            return is_dir($sitePath.'/web'.$uri)
                ? $sitePath.'/web'.$uri.'/index.php'
                : $sitePath.'/web'.$uri;
        }

        return $sitePath.'/web/index.php';
    }
}
