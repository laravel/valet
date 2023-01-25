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
        return is_dir($sitePath.'/vendor/contao') && file_exists($sitePath.'/web/app.php');
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        if ($this->isActualFile($staticFilePath = $sitePath.'/web'.$uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        if ($uri === '/install.php') {
            return $sitePath.'/web/install.php';
        }

        if (0 === strncmp($uri, '/app_dev.php', 12)) {
            $_SERVER['SCRIPT_NAME'] = '/app_dev.php';
            $_SERVER['SCRIPT_FILENAME'] = $sitePath.'/app_dev.php';

            return $sitePath.'/web/app_dev.php';
        }

        return $sitePath.'/web/app.php';
    }
}
