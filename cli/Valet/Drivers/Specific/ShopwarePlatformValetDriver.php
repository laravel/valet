<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\ValetDriver;

class ShopwarePlatformValetDriver extends ValetDriver
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
        $corePath = realpath($sitePath.'/vendor/shopware/core');
        $platformPath = realpath($sitePath.'/vendor/shopware/platform');
        
        if (($corePath !== false && is_dir($corePath)) || ($platformPath !== false && is_dir($platformPath))) {
            return true;
        }
        return false;
    }
    /**
    * Determine if the incoming request is for a static file.
    *
    * @param  string  $sitePath
    * @param  string  $siteName
    * @param  string  $uri
    * @return string|false
    */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        if (file_exists($staticFilePath = $sitePath.'/public'.$uri)) {
            return $staticFilePath;
        } elseif ($this->isActualFile($staticFilePath = $sitePath.$uri)) {
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
        $this->loadServerEnvironmentVariables($sitePath, $siteName);

        if ($this->isInstallPath($sitePath, $uri)) {
            $installPath = $this->buildInstallPath($sitePath, $uri);
            $_SERVER['SCRIPT_FILENAME'] = $installPath;
            $_SERVER['SCRIPT_NAME'] = str_replace($sitePath.'/public', '', $installPath);
            $_SERVER['DOCUMENT_ROOT'] = $sitePath;
            return $installPath;
        }

        if ($this->isUpdatePath($sitePath, $uri)) {
            $updatePath = $this->buildUpdatePath($sitePath, $uri);
            $_SERVER['SCRIPT_FILENAME'] = $updatePath;
            $_SERVER['SCRIPT_NAME'] = str_replace($sitePath.'/public', '', $updatePath);
            $_SERVER['DOCUMENT_ROOT'] = $sitePath;
            return $updatePath;
        }

        return $sitePath.'/public/index.php';
    }
    /**
     * check if uri contains shopware install path
     *
     * @param string $sitePath
     * @param string $uri
     * @return bool
     */
    protected function isInstallPath($sitePath, $uri)
    {
        return (strpos($uri, '/recovery/install') !== false);
    }
    /**
     * check if uri contains shopware update path
     *
     * @param string $sitePath
     * @param string $uri
     * @return bool
     */
    protected function isUpdatePath($sitePath, $uri)
    {
        return (strpos($uri, '/recovery/update') !== false);
    }
    /**
     * build shopware install url
     *
     * @param $sitePath
     * @param $uri
     * @return string
     */
    protected function buildInstallPath($sitePath, $uri)
    {
        return $sitePath . '/public/recovery/install/index.php';
    }
    /**
     * build shopware update url
     *
     * @param $sitePath
     * @param $uri
     * @return string
     */
    protected function buildUpdatePath($sitePath, $uri)
    {
        return $sitePath . '/public/recovery/update/index.php';
    }
}
