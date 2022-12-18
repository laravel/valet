<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\ValetDriver;

class NeosValetDriver extends ValetDriver
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
        return file_exists($sitePath.'/flow') && is_dir($sitePath.'/Web');
    }

    /**
     * Take any steps necessary before loading the front controller for this driver.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return void
     */
    public function beforeLoading(string $sitePath, string $siteName, string $uri): void
    {
        putenv('FLOW_CONTEXT=Development');
        putenv('FLOW_REWRITEURLS=1');
        $_SERVER['SCRIPT_FILENAME'] = $sitePath . '/Web/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string|false
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri): string|false
    {
        if ($this->isActualFile($staticFilePath = $sitePath.'/Web'.$uri)) {
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
     * @return string
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
    {
        return $sitePath.'/Web/index.php';
    }
}
