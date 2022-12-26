<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\BasicValetDriver;

class BedrockValetDriver extends BasicValetDriver
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
        return file_exists($sitePath.'/web/app/mu-plugins/bedrock-autoloader.php') ||
              (is_dir($sitePath.'/web/app/') &&
               file_exists($sitePath.'/web/wp-config.php') &&
               file_exists($sitePath.'/config/application.php'));
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
    public function isStaticFile(string $sitePath, string $siteName, string $uri)
    {
        $staticFilePath = $sitePath.'/web'.$uri;

        if ($this->isActualFile($staticFilePath)) {
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
        return parent::frontControllerPath(
            $sitePath.'/web',
            $siteName,
            $this->forceTrailingSlash($uri)
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
        if (substr($uri, -1 * strlen('/wp/wp-admin')) == '/wp/wp-admin') {
            header('Location: '.$uri.'/');
            exit;
        }

        return $uri;
    }
}
