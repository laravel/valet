<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\BasicValetDriver;

class BedrockValetDriver extends BasicValetDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return $this->composerRequires($sitePath, 'roots/bedrock-autoloader') ||
            file_exists($sitePath.'/web/app/mu-plugins/bedrock-autoloader.php') ||
            file_exists($sitePath.'/public/content/mu-plugins/bedrock-autoloader.php') ||
            (is_dir($sitePath.'/web/app/') &&
                file_exists($sitePath.'/web/wp-config.php') &&
                file_exists($sitePath.'/config/application.php')) ||
            (is_dir($sitePath.'/public/content/') &&
                file_exists($sitePath.'/public/wp-config.php') &&
                file_exists($sitePath.'/bedrock/application.php'));
    }

    /**
     * Determine the name of the directory where the front controller lives.
     */
    public function frontControllerDirectory($sitePath): string
    {
        $dirs = ['web', 'public'];

        foreach ($dirs as $dir) {
            if (is_dir($sitePath.'/'.$dir)) {
                return $dir;
            }
        }

        // Give up, and just return the default
        return 'public';
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)
    {
        $frontControllerDirectory = $this->frontControllerDirectory($sitePath);

        if ($this->isActualFile($staticFilePath = $sitePath.'/'.$frontControllerDirectory.$uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        $frontControllerDirectory = $this->frontControllerDirectory($sitePath);

        return parent::frontControllerPath(
            $sitePath.'/'.$frontControllerDirectory,
            $siteName,
            $this->forceTrailingSlash($uri)
        );
    }

    /**
     * Redirect to uri with trailing slash.
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
