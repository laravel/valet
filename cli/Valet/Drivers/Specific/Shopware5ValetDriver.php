<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\ValetDriver;

class Shopware5ValetDriver extends ValetDriver
{
    /**
     * determine if the driver serves a shopware request.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     * @return bool
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        if (file_exists($sitePath . '/shopware.php')) {
            return true;
        }

        return false;
    }

    /**
     * determine if the incoming request is for a static file.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     * @return string|false
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        if (file_exists($staticFilePath = $sitePath . $uri)) {
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
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
    {
        $this->loadServerEnvironmentVariables($sitePath, $siteName);

        if ($this->isRecoveryPath($sitePath, $uri)) {
            $installPath                = $this->buildInstallPath($sitePath, $uri);
            $_SERVER['SCRIPT_FILENAME'] = $installPath;
            $_SERVER['SCRIPT_NAME']     = str_replace($sitePath, '', $installPath);
            $_SERVER['DOCUMENT_ROOT']   = $sitePath;

            return $installPath;
        }

        if ($this->isUpdaterPath($sitePath, $uri)) {
            $updaterPath                = $this->buildUpdaterPath($sitePath, $uri);
            $_SERVER['SCRIPT_FILENAME'] = $updaterPath;
            $_SERVER['SCRIPT_NAME']     = str_replace($sitePath, '', $updaterPath);
            $_SERVER['DOCUMENT_ROOT']   = $sitePath;

            return $updaterPath;
        }

        return $sitePath . '/shopware.php';
    }

    /**
     * check if uri contains shopware install path
     *
     * @param string $sitePath
     * @param string $uri
     * @return bool
     */
    protected function isRecoveryPath($sitePath, $uri)
    {
        return (strpos($uri, 'recovery/install') !== false);
    }

    /**
     * check if uri contains shopware update path
     *
     * @param string $sitePath
     * @param string $uri
     * @return bool
     */
    protected function isUpdaterPath($sitePath, $uri)
    {
        return (strpos($uri, 'recovery/update') !== false);
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
        return $sitePath . '/recovery/install/index.php';
    }

    /**
     * build shopware install url
     *
     * @param $sitePath
     * @param $uri
     * @return string
     */
    protected function buildUpdaterPath($sitePath, $uri)
    {
        return $sitePath . '/recovery/update/index.php';
    }
}
