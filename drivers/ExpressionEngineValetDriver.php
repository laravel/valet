<?php

class ExpressionEngineValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return bool
     */
    public function serves($sitePath, $siteName, $uri)
    {
        return is_dir($sitePath . '/system');
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        if (file_exists($sitePath . $uri) &&
            !is_dir($sitePath . $uri) &&
            pathinfo($sitePath . $uri)['extension'] != 'php'
        ) {
            return $sitePath . $uri;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        if (file_exists($frontControllerPath = $sitePath . '/index.php')) {
            chdir($sitePath);
            return $frontControllerPath;
        } elseif (file_exists($frontControllerPath = $sitePath . '/public/index.php')) {
            chdir($sitePath . '/public');
            return $frontControllerPath;
        }
    }
}
