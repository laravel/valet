<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\ValetDriver;

class DrupalValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        $sitePath = $this->addSubdirectory($sitePath);

        /**
         * /misc/drupal.js = Drupal 7
         * /core/lib/Drupal.php = Drupal 8.
         */
        if (file_exists($sitePath.'/misc/drupal.js') ||
          file_exists($sitePath.'/core/lib/Drupal.php')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        $sitePath = $this->addSubdirectory($sitePath);

        if (file_exists($sitePath.$uri) &&
            ! is_dir($sitePath.$uri) &&
            pathinfo($sitePath.$uri)['extension'] != 'php') {
            return $sitePath.$uri;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        $sitePath = $this->addSubdirectory($sitePath);

        if (! isset($_GET['Q']) && ! empty($uri) && $uri !== '/' && strpos($uri, '/jsonapi/') === false) {
            $_GET['Q'] = $uri;
        }

        $matches = [];
        if (preg_match('/^\/(.*?)\.php/', $uri, $matches)) {
            $filename = $matches[0];
            if (file_exists($sitePath.$filename) && ! is_dir($sitePath.$filename)) {
                $_SERVER['SCRIPT_FILENAME'] = $sitePath.$filename;
                $_SERVER['SCRIPT_NAME'] = $filename;

                return $sitePath.$filename;
            }
        }

        // Fallback
        $_SERVER['SCRIPT_FILENAME'] = $sitePath.'/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        return $sitePath.'/index.php';
    }

    /**
     * Add any matching subdirectory to the site path.
     */
    public function addSubdirectory($sitePath): string
    {
        $paths = array_map(function ($subDir) use ($sitePath) {
            return "$sitePath/$subDir";
        }, $this->possibleSubdirectories());

        $foundPaths = array_filter($paths, function ($path) {
            return file_exists($path);
        });

        // If paths are found, return the first one.
        if (! empty($foundPaths)) {
            return array_shift($foundPaths);
        }

        // If there are no matches, return the original path.
        return $sitePath;
    }

    /**
     * Return an array of possible subdirectories.
     */
    private function possibleSubdirectories(): array
    {
        return ['docroot', 'public', 'web'];
    }
}
