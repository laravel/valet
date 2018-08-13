<?php

class Shopware5ValetDriver extends ValetDriver
{
    /**
     * determine if the driver serves a shopware request.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return bool
     */
    public function serves($sitePath, $siteName, $uri)
    {
        if (file_exists($sitePath . '/shopware.php')) {
            return true;
        }

        return false;
    }

    /**
     * determine if the incoming request is for a static file.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        if (file_exists($staticFilePath = $sitePath . $uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        if ($this->isRecoveryPath($sitePath, $uri)) {
            $installPath = $this->buildInstallPath($sitePath, $uri);
            $_SERVER['SCRIPT_FILENAME'] = $installPath;
            $_SERVER['SCRIPT_NAME'] = str_replace($sitePath, '', $installPath);
            $_SERVER['DOCUMENT_ROOT'] = $sitePath;

            return $installPath;
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
}
