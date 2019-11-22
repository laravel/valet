<?php

class NeosValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     * @return bool
     */
    public function serves($sitePath, $siteName, $uri)
    {
        return file_exists($sitePath . '/flow') && is_dir($sitePath . '/Web');
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        if ($this->isActualFile($staticFilePath = $sitePath . '/Web' . $uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        putenv('FLOW_CONTEXT=Development');
        putenv('FLOW_REWRITEURLS=1');
        $_SERVER['SCRIPT_FILENAME'] = $sitePath . '/Web/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        return $sitePath . '/Web/index.php';
    }
}
