<?php

class ShopwareValetDriver extends BasicValetDriver
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
        return file_exists($sitePath . '/shopware.php');
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
        if ($this->isActualFile($staticFilePath = $sitePath . '/' . ltrim($uri, '/'))) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Format the site path and URI with a trailing "index.php".
     *
     * @param  string  $sitePath
     * @param  string  $uri
     * @return string
     */
    protected function asPhpIndexFileInDirectory($sitePath, $uri)
    {
        if (strpos($uri, '/recovery/install') !== false) {
            return $sitePath . '/recovery/install/index.php';
        }

        return parent::asPhpIndexFileInDirectory($sitePath, $uri);
    }

    /**
     * Format the incoming site path as root "index.php" file path.
     *
     * @param  string  $sitePath
     * @return string
     */
    protected function asRootPhpIndexFile($sitePath)
    {
        return $sitePath.'/shopware.php';
    }

    /**
     * Format the incoming site path as a "public/index.php" file path.
     *
     * @param  string  $sitePath
     * @return string
     */
    protected function asPublicPhpIndexFile($sitePath)
    {
        return $sitePath.'/shopware.php';
    }

    /**
     * Format the incoming site path as a "public/index.php" file path.
     *
     * @param  string  $sitePath
     * @return string
     */
    protected function asPublicHtmlIndexFile($sitePath)
    {
        return $sitePath.'/index.html';
    }
}
