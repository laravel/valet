<?php

class CsCartValetDriver extends ValetDriver
{
    const INSTALL_PATHS = ['/install/', '/install', '/install/index.php'];
    const INSTALL_CONTROLLER = '/install/index.php';


    protected $admin;
    protected $customer;


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
        return is_dir($sitePath.'/app/Tygh');
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
        if (file_exists($staticFilePath = $sitePath.$uri) && !in_array($uri, self::INSTALL_PATHS)) {
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
        if ($this->validateInstallUrl($uri)) {
            return $sitePath.self::INSTALL_CONTROLLER;
        }

        $this->initCsCartConfiguration($sitePath);

        if ($uri == $this->admin) {
            return $sitePath.$this->admin;
        }

        return $sitePath.$this->customer;
    }

    protected function initCsCartConfiguration($sitePath) {
        $configuration = file_get_contents($sitePath."/config.local.php");

        $configSearch = "/^\\\$config\\['(customer|admin)_index'\\][^'*]*'([^']*)';/m";

        preg_match_all($configSearch, $configuration, $configMatch, PREG_SET_ORDER);

        foreach ($configMatch as $match) {
            $var = $match[1];
            $this->$var = '/'.$match[2];
        }

    }

    protected function validateInstallUrl($uri) {
        if (in_array($uri, self::INSTALL_PATHS)) {
            return true;
        }

        return false;
    }
}
