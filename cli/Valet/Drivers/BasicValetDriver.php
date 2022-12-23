<?php

namespace Valet\Drivers;

class BasicValetDriver extends ValetDriver
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
        return true;
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
        $_SERVER['PHP_SELF'] = $uri;
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string|false
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri): string|false
    {
        if (file_exists($staticFilePath = $sitePath.rtrim($uri, '/').'/index.html')) {
            return $staticFilePath;
        } elseif ($this->isActualFile($staticFilePath = $sitePath.$uri)) {
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
     * @return string|null
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        $uri = rtrim($uri, '/');

        $candidates = [
            $sitePath.$uri,
            $sitePath.$uri.'/index.php',
            $sitePath.'/index.php',
            $sitePath.'/index.html',
        ];

        foreach ($candidates as $candidate) {
            if ($this->isActualFile($candidate)) {
                $_SERVER['SCRIPT_FILENAME'] = $candidate;
                $_SERVER['SCRIPT_NAME'] = str_replace($sitePath, '', $candidate);
                $_SERVER['DOCUMENT_ROOT'] = $sitePath;

                return $candidate;
            }
        }

        return null;
    }
}
