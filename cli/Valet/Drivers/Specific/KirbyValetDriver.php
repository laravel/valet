<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\ValetDriver;

class KirbyValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return is_dir($sitePath.'/kirby');
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        if ($this->isActualFile($staticFilePath = $sitePath.$uri)) {
            return $staticFilePath;
        } elseif ($this->isActualFile($staticFilePath = $sitePath.'/public'.$uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        $scriptName = '/index.php';

        if ($this->isActualFile($sitePath.'/index.php')) {
            $indexPath = $sitePath.'/index.php';
        }

        if ($isAboveWebroot = $this->isActualFile($sitePath.'/public/index.php')) {
            $indexPath = $sitePath.'/public/index.php';
        }

        if (preg_match('/^\/panel/', $uri) && $this->isActualFile($sitePath.'/panel/index.php')) {
            $scriptName = '/panel/index.php';
            $indexPath = $sitePath.'/panel/index.php';
        }

        $sitePathPrefix = ($isAboveWebroot) ? $sitePath.'/public' : $sitePath;

        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
        $_SERVER['SCRIPT_NAME'] = $scriptName;
        $_SERVER['SCRIPT_FILENAME'] = $sitePathPrefix.$scriptName;

        return $indexPath;
    }
}
