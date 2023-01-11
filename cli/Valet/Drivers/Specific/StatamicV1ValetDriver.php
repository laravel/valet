<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\ValetDriver;

class StatamicV1ValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath.'/_app/core/statamic.php');
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        if (strpos($uri, '/_add-ons') === 0 || strpos($uri, '/_app') === 0 || strpos($uri, '/_content') === 0 ||
            strpos($uri, '/_cache') === 0 || strpos($uri, '/_config') === 0 || strpos($uri, '/_logs') === 0 ||
            $uri === '/admin'
        ) {
            return false;
        }

        if ($this->isActualFile($staticFilePath = $sitePath.$uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        if (strpos($uri, '/admin.php') === 0) {
            $_SERVER['SCRIPT_NAME'] = '/admin.php';

            return $sitePath.'/admin.php';
        }

        if ($uri === '/admin') {
            $_SERVER['SCRIPT_NAME'] = '/admin/index.php';

            return $sitePath.'/admin/index.php';
        }

        $_SERVER['SCRIPT_NAME'] = '/index.php';

        return $sitePath.'/index.php';
    }
}
