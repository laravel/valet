<?php

class WordpressJohnpblochValetDriver extends ValetDriver
{

    private $installPath;
    private $publicPath;

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
            && is_dir($sitePath . '/vendor/johnpbloch/wordpress-core-installer')) {

            $this->installPath = $this->getinstallPath($sitePath);
            $this->publicPath = $this->getPublicPath();

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
        if (file_exists($realPath = $this->publicPath . $uri) && !is_dir($realPath) && !$this->fileIsPHP($uri)) {
            return $realPath;
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
        if (($realPath = $this->getFrontControllerPath($this->publicPath, $uri))
            || ($realPath = $this->getFrontControllerPath($this->installPath, $uri))) {

            return $realPath;
        }
    }

    /**
     * Parses composer.json for the install path defined for fancyguy/johnpbloch-installer
     * @param  string $path
     * @return string
     */
    private function getInstallPath($path)
    {
        $composer = json_decode(file_get_contents($path . '/composer.json'), true);

        return $path .= '/'. $composer['extra']['wordpress-install-dir'];
    }

    /**
     * Parses composer.json for the install path defined for fancyguy/johnpbloch-installer
     * @param  string $path
     * @return string
     */
    private function getPublicPath()
    {
        $directories = explode('/', $this->installPath);
        array_pop($directories);

        return implode('/', $directories);
    }

    /**
     * Determines what file should process request.
     * @param  string $path
     * @param  string $uri
     * @return string|bool
     */
    private function getFrontControllerPath($path, $uri)
    {
        // If request is for real directory, check for index.php (e.g. /wp-admin/)
        if (is_dir($dirPath = $path . $uri) && file_exists($indexedDirPath = $dirPath . '/index.php')) {
            return $indexedDirPath;

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
     * Check if the URI points to a PHP file
     * @param  string $uri
     * @return bool
     */
    private function fileIsPHP($uri)
    {
        return (pathinfo($uri, PATHINFO_EXTENSION) === 'php');
    }
}
