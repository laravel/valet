<?php
namespace Valet\Drivers\Custom;

use Valet\Drivers\ValetDriver;

class RadicleValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return bool
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath . '/public/content/mu-plugins/bedrock-autoloader.php') &&
               file_exists($sitePath . '/public/wp-config.php') &&
               file_exists($sitePath . '/bedrock/application.php');
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string       $sitePath
     * @param  string       $siteName
     * @param  string       $uri
     * @return string|false
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        $staticFilePath = $sitePath . '/public' . $uri;
        if ($this->isActualFile($staticFilePath)) {
            return $staticFilePath;
        }
        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return string
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
    {
        $_SERVER['PHP_SELF'] = $uri;
        if (strpos($uri, '/wp/') === 0) {
            return is_dir($sitePath . '/public' . $uri)
                            ? $sitePath . '/public' . $this->forceTrailingSlash($uri) . '/index.php'
                            : $sitePath . '/public' . $uri;
        }
        return $sitePath . '/public/index.php';
    }

    /**
     * Redirect to uri with trailing slash.
     *
     * @param  string $uri
     * @return string
     */
    private function forceTrailingSlash(string $uri)
    {
        if (substr($uri, -1 * strlen('/wp/wp-admin')) == '/wp/wp-admin') {
            header('Location: ' . $uri . '/');
            die;
        }
        return $uri;
    }
}
