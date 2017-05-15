<?php

/**
 * Roadiz Valet driver.
 *
 * This driver will serve ONLY Roadiz Standard edition
 * websites.
 */
class RoadizValetDriver extends ValetDriver
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
        if (file_exists($sitePath.'/bin/roadiz') &&
            file_exists($sitePath.'/web/clear_cache.php') &&
            file_exists($sitePath.'/web/dev.php') &&
            file_exists($sitePath.'/web/install.php') &&
            file_exists($sitePath.'/web/themes') &&
            file_exists($sitePath.'/web/index.php')) {
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
        if ($this->isActualFile($staticFilePath = $sitePath.'/web/'.$uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * Roadiz uses many entry points for each application environments:
     * - Production = index.php
     * - Development = dev.php
     * - First install = install.php
     * - Preview mode = preview.php
     * - Clearing PHPFPM/CGI caches = clear_cache.php
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        // Default index path
        $indexPath = $sitePath.'/web/index.php';
        $scriptName = '/index.php';

        // Check if the first URL segment matches any of the defined entry-points
        $entryPoints = ['dev.php', 'clear_cache.php', 'install.php', 'preview.php'];
        $parts = explode('/', $uri);

        if (count($parts) > 1 && in_array($parts[1], $entryPoints)) {
            $indexPath = $sitePath.'/web/'. $parts[1];
            $scriptName = $parts[1];
        }

        $_SERVER['SCRIPT_FILENAME'] = $indexPath;
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
        $_SERVER['SCRIPT_NAME'] = $scriptName;

        return $indexPath;
    }
}
