<?php

class CodeIgniterValetDriver extends ValetDriver
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
        // The CodeIgniter system path is configurable. This makes checking for a system file difficult. Instead, let's
        // attempt to find the index file (predictable location), open it, and search for an identifiable string.

        if (! $indexPath = $this->findIndexPath($sitePath)) {
            return false;
        }

        $indexLines = file($indexPath.'/index.php');

        // We should grab a match by the fifth line.
        for ($line = 0; $line <= 4; $line++) {
            if (strpos($indexLines[$line], 'CodeIgniter') !== false) {
                return true;
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
        $indexPath = $this->findIndexPath($sitePath);

        if ($this->isActualFile($staticFilePath = $indexPath.$uri)) {
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
        return $this->findIndexPath($sitePath).'/index.php';
    }

    /**
     * Checks common paths for the existence of an index file.
     *
     * @param  string  $sitePath
     * @return string|false
     */
    public function findIndexPath($sitePath)
    {
        // The root of the project (CI default) and some common public directory names.
        $possibleLocations = ['', '/public', '/public_html', '/www'];

        foreach ($possibleLocations as $location) {
            $indexPath = $sitePath.$location;
            if ($this->isActualFile($indexPath.'/index.php')) {
                return $indexPath;
            }
        }

        return false;
    }
}
