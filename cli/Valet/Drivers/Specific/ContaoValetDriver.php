<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\ValetDriver;

class ContaoValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        $frontControllerDirectory = $this->frontControllerDirectory($sitePath);

        return is_dir($sitePath.'/vendor/contao') && file_exists($sitePath.'/'.$frontControllerDirectory.'/index.php');
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
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/* : string|false */
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

        if (strncmp($uri, '/contao-manager.phar.php', 24) === 0) {
            $_SERVER['SCRIPT_NAME'] = '/contao-manager.phar.php';
            $_SERVER['SCRIPT_FILENAME'] = $sitePath.'/contao-manager.phar.php';
            return $sitePath.'/'.$frontControllerDirectory.'/contao-manager.phar.php';
        }

        if (strncmp($uri, '/preview.php', 12) === 0) {
            $_SERVER['SCRIPT_NAME'] = '/preview.php';
            $_SERVER['SCRIPT_FILENAME'] = $sitePath.'/preview.php';

            return $sitePath.'/'.$frontControllerDirectory.'/preview.php';
        }

        return $sitePath.'/'.$frontControllerDirectory.'/index.php';
    }
}
