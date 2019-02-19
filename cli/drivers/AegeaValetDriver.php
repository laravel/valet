<?php

class AegeaValetDriver extends ValetDriver
{
    /**
     * Rules and file paths
     *
     * @var array
     */
    protected $checks = [
        'file_exists' => [
            'system/default/config.php',
            'user/config.php',
        ],
        'is_dir' => [
            'themes',
        ],
    ];

    /**
     * Determine if the driver serves the request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return bool
     */
    public function serves($sitePath, $siteName, $uri)
    {
        foreach ($this->checks as $func => $paths) {
            foreach ($paths as $path) {
                if (!$func($sitePath.'/'.$path)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        if ($this->isActualFile($staticFilePath = $sitePath.'/'.$uri)) {
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
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        if (!array_key_exists('go', $_REQUEST)) {
            $_GET['go'] = $uri;
        }

        return $sitePath.'/index.php';
    }
}

