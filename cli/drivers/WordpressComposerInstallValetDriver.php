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
        if (file_exists($sitePath . '/composer.json')
            && (file_exists($sitePath . '/wp-config.php')
                || $this->isJohnpblochInstall($sitePath)
                || $this->isWebrootInstall($sitePath))
        ) {
            return true;
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

        if ($this->getStaticFilePath($sitePath, $uri)) {
            return $this->getStaticFilePath($sitePath, $uri);
        }

        if ($this->isWebrootInstall($sitePath)) {
            $sitePath = $this->getWebrootInstallPath($sitePath);

            if ($this->getStaticFilePath($sitePath, $uri)) {
                return $this->getStaticFilePath($sitePath, $uri);
            }
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

        if ($this->getFrontControllerPath($sitePath, $uri)) {
            return $this->getFrontControllerPath($sitePath, $uri);
        }

        if ($this->isWebrootInstall($sitePath)) {
            $webrootSitePath = $this->getWebrootInstallPath($sitePath);

            if ($this->getFrontControllerPath($webrootSitePath, $uri)) {
                return $this->getFrontControllerPath($webrootSitePath, $uri);
            }
        }
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
                && file_exists($path . '/config/application.php'))
        );
    }

    /**
     * Checks if current install is using [fancyguy/webroot-installer](https://github.com/fancyguy/webroot-installer)
     * @param  string  $path
     * @return boolean
     */
    private function isJohnpblochInstall($path)
    {
        return (is_dir($path . '/vendor/johnpbloch/wordpress-core-installer'));
    }

    /**
     * Checks if current install is using [fancyguy/webroot-installer](https://github.com/fancyguy/webroot-installer)
     * @param  string  $path
     * @return boolean
     */
    private function isWebrootInstall($path)
    {
        return (is_dir($path . '/vendor/fancyguy/webroot-installer'));
    }

    /**
     * parses composer.json for the install path defined for fancyguy/webroot-installer
     * @param  string $path
     * @return string
     */
    private function getWebrootInstallPath($path)
    {
        $composer = json_decode(file_get_contents($path . '/composer.json'), true);
        return $path .= '/'. $composer['extra']['webroot-dir'];
    }

    /**
     * Helper function to determine whether to serve a static file.
     * @param  string $path
     * @param  string $uri
     * @return string|bool
     */
    private function getStaticFilePath($path, $uri)
    {
        if (file_exists($staticPath = $path . $uri) && !$this->fileIsPHP($uri) && !is_dir($staticPath)) {
            return $staticPath;
        }

        return false;
    }

    /**
     * Helper function to determine what file should process request.
     * @param  string $path
     * @param  string $uri
     * @return string|bool
     */
    private function getFrontControllerPath($path, $uri)
    {
        // If request is for real directory, check for index.php (e.g. /wp-admin/)
        if (is_dir($staticPath = $path . $uri) && file_exists($indexedStaticPath = $staticPath . '/index.php')) {
            return $indexedStaticPath;

        // If request is for specific php file, use it if it exists (e.g. /wp-admin/edit.php, /wp-admin/upload.php)
        } elseif ($this->fileIsPHP($uri) && file_exists($phpFile = $path . $uri)) {
            $_SERVER['PHP_SELF'] = $uri;
            return preg_replace('/\/$/', '', $phpFile);

        // If nothing else, check the provided site path for an index.php (i.e. lets WP rewrite rules handle things)
        } elseif (file_exists($rootIndex = $path . '/index.php')) {
            return $rootIndex;
        }

        return false;
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
