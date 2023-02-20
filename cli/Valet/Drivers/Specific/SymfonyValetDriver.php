<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\ValetDriver;

class SymfonyValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return (file_exists($sitePath.'/web/app_dev.php') || file_exists($sitePath.'/web/app.php')) &&
               (file_exists($sitePath.'/app/AppKernel.php')) || (file_exists($sitePath.'/public/index.php')) &&
               (file_exists($sitePath.'/src/Kernel.php'));
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        if ($this->isActualFile($staticFilePath = $sitePath.'/web/'.$uri)) {
            return $staticFilePath;
        } elseif ($this->isActualFile($staticFilePath = $sitePath.'/public/'.$uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        $frontControllerPath = null;

        if (file_exists($path = $sitePath.'/web/app_dev.php')) {
            $frontControllerPath = $path;
        } elseif (file_exists($path = $sitePath.'/web/app.php')) {
            $frontControllerPath = $path;
        } elseif (file_exists($path = $sitePath.'/public/index.php')) {
            $frontControllerPath = $path;
        }

        $_SERVER['SCRIPT_FILENAME'] = $frontControllerPath;

        return $frontControllerPath;
    }
}
