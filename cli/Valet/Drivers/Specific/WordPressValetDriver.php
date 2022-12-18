<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\BasicValetDriver;

class WordPressValetDriver extends BasicValetDriver
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
        return file_exists($sitePath.'/wp-config.php') || file_exists($sitePath.'/wp-config-sample.php');
    }

    /**
     * Take any steps necessary before loading the front controller for this driver.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return void
     */
    public function beforeLoading(string $sitePath, string $siteName, string $uri): void
    {
        $_SERVER['PHP_SELF'] = $uri;
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
    {
        return parent::frontControllerPath(
            $sitePath, $siteName, $this->forceTrailingSlash($uri)
        );
    }

    /**
     * Redirect to uri with trailing slash.
     *
     * @param  string  $uri
     * @return string
     */
    private function forceTrailingSlash($uri)
    {
        if (substr($uri, -1 * strlen('/wp-admin')) == '/wp-admin') {
            header('Location: '.$uri.'/');
            exit;
        }

        return $uri;
    }
}
