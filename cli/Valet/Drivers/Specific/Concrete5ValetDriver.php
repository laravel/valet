<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\BasicValetDriver;

class Concrete5ValetDriver extends BasicValetDriver
{
    /**
     * If a concrete directory exists, it's probably c5.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath.'/concrete/config/install/base');
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        if (stripos($uri, '/application/files') === 0) {
            return $sitePath.$uri;
        }

        return parent::isStaticFile($sitePath, $siteName, $uri);
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        if (! getenv('CONCRETE5_ENV')) {
            putenv('CONCRETE5_ENV=valet');
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

        $_SERVER['SCRIPT_FILENAME'] = $sitePath.'/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        return $sitePath.'/index.php';
    }
}
