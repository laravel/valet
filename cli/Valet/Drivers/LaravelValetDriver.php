<?php

namespace Valet\Drivers;

class LaravelValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return bool
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath.'/public/index.php') &&
               file_exists($sitePath.'/artisan');
    }

    /**
     * Take any steps necessary before loading the front controller for this driver.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return void
     */
    public function beforeLoading(string $sitePath, string $siteName, string $uri): void
    {
        // Shortcut for getting the "local" hostname as the HTTP_HOST
        if (isset($_SERVER['HTTP_X_ORIGINAL_HOST'], $_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
        }
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string|false
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        if (file_exists($staticFilePath = $sitePath.'/public'.$uri)
           && is_file($staticFilePath)) {
            return $staticFilePath;
        }

        $storageUri = $uri;

        if (strpos($uri, '/storage/') === 0) {
            $storageUri = substr($uri, 8);
        }

        if ($this->isActualFile($storagePath = $sitePath.'/storage/app/public'.$storageUri)) {
            return $storagePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string|null
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        return $sitePath.'/public/index.php';
    }
}
