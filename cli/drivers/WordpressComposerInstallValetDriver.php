<?php

class WordpressComposerInstallValetDriver extends ValetDriver
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
        if (file_exists($composerFile = $sitePath . '/composer.json')) {
            if (file_exists($sitePath . '/wp-config.php')) {
                return true;
            }

            $composerJson = json_decode(file_get_contents($composerFile));

            if (isset($composerJson->extra)) {
                $extra = (array) $composerJson->extra;

                if (isset($extra['wordpress-install-dir'])) {
                    return true;
                }
            }
        }

        return false;
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
        // Look in public web directory
        if ($this->isBedrockInstall($sitePath)) {
            $sitePath .= '/web';
        }

        if (file_exists($staticFilePath = $sitePath . $uri) && !$this->fileIsPHP($uri) && !is_dir($staticFilePath)) {
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
        // Look in public web directory
        if ($this->isBedrockInstall($sitePath)) {
            $sitePath .= '/web';
        }

        if (is_dir($staticFilePath = $sitePath . $uri)) {
            return $staticFilePath . '/index.php';
        } elseif ($this->fileIsPHP($uri)) {
            $_SERVER['PHP_SELF'] = $uri;
            return $sitePath . preg_replace('/\/$/', '', $uri);
        }

        return $sitePath . '/index.php';
    }

    /**
     * Checks if current install is [roots/bedrock](https://github.com/roots/bedrock)
     * @param  string  $path
     * @return boolean
     */
    private function isBedrockInstall($path)
    {
        return (file_exists($path . '/web/app/mu-plugins/bedrock-autoloader.php')
            || (is_dir($path . '/web/app/')
                && file_exists($path . '/web/wp-config.php')
                && file_exists($path . '/config/application.php')
            )
        );
    }

    /**
     * Check if the uri points to a PHP file
     * @param  string $uri
     * @return bool
     */
    private function fileIsPHP($uri)
    {
        return (pathinfo($uri, PATHINFO_EXTENSION) === 'php');
    }
}
