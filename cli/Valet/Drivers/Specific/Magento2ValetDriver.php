<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\ValetDriver;

class Magento2ValetDriver extends ValetDriver
{
    /**
     * Holds the MAGE_MODE from app/etc/config.php or $ENV.
     *
     * Can't be correctly typed yet because PHP 7.3.
     *
     * @param string|null
     */
    private $mageMode = null;

    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath.'/bin/magento') && file_exists($sitePath.'/pub/index.php');
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        $this->checkMageMode($sitePath);

        $uri = $this->handleForVersions($uri);
        $route = parse_url(substr($uri, 1))['path'];

        $pub = '';
        if ('developer' === $this->mageMode) {
            $pub = 'pub/';
        }

        if (! $this->isPubDirectory($sitePath, $route, $pub)) {
            return false;
        }

        $magentoPackagePubDir = $sitePath;
        if ('developer' !== $this->mageMode) {
            $magentoPackagePubDir .= '/pub';
        }

        $file = $magentoPackagePubDir.'/'.$route;

        if (file_exists($file)) {
            return $magentoPackagePubDir.$uri;
        }

        if (strpos($route, $pub.'static/') === 0) {
            $route = preg_replace('#'.$pub.'static/#', '', $route, 1);
            $_GET['resource'] = $route;
            include $magentoPackagePubDir.'/'.$pub.'static.php';
            exit;
        }

        if (strpos($route, $pub.'media/') === 0) {
            include $magentoPackagePubDir.'/'.$pub.'get.php';
            exit;
        }

        return false;
    }

    /**
     * Rewrite URLs that look like "versions12345/" to remove
     * the versions12345/ part.
     */
    private function handleForVersions($route): string
    {
        return preg_replace('/version\d*\//', '', $route);
    }

    /**
     * Determine the current MAGE_MODE.
     */
    private function checkMageMode($sitePath): void
    {
        if (null !== $this->mageMode) {
            // We have already figure out mode, no need to check it again
            return;
        }

        if (! file_exists($sitePath.'/index.php')) {
            $this->mageMode = 'production'; // Can't use developer mode without index.php in project root

            return;
        }

        $mageConfig = [];

        if (file_exists($sitePath.'/app/etc/env.php')) {
            $mageConfig = require $sitePath.'/app/etc/env.php';
        }

        if (array_key_exists('MAGE_MODE', $mageConfig)) {
            $this->mageMode = $mageConfig['MAGE_MODE'];
        }
    }

    /**
     * Checks to see if route is referencing any directory inside pub. This is a dynamic check so that if any new
     * directories are added to pub this driver will not need to be updated.
     */
    private function isPubDirectory($sitePath, $route, $pub = ''): bool
    {
        $sitePath .= '/pub/';
        $dirs = glob($sitePath.'*', GLOB_ONLYDIR);

        $dirs = str_replace($sitePath, '', $dirs);

        foreach ($dirs as $dir) {
            if (strpos($route, $pub.$dir.'/') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        $this->checkMageMode($sitePath);

        if ('developer' === $this->mageMode) {
            $_SERVER['DOCUMENT_ROOT'] = $sitePath;

            return $sitePath.'/index.php';
        }

        $_SERVER['DOCUMENT_ROOT'] = $sitePath.'/pub';

        return $sitePath.'/pub/index.php';
    }
}
