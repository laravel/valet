<?php

class MagentoValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return void
     */
    public function serves($sitePath, $siteName, $uri)
    {
        return file_exists($sitePath . '/bin/magento') && file_exists($sitePath.'/pub/index.php');
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
        $route = parse_url(substr($uri, 1))["path"];

        if (strpos($route, 'pub/errors/default/') === 0) {
            $route = preg_replace('#pub/errors/default/#', 'errors/default/', $route, 1);
        }

        if (
            strpos($route, 'media/') === 0 ||
            strpos($route, 'opt/') === 0 ||
            strpos($route, 'static/') === 0 ||
            strpos($route, 'errors/default/css/') === 0 ||
            strpos($route, 'errors/default/images/') === 0
        ) {
            $magentoPackagePubDir = $sitePath."/pub";

            $file = $magentoPackagePubDir.'/'.$route;

            if (file_exists($file)) {
                return $magentoPackagePubDir.$uri;
            } else {
                if (strpos($route, 'static/') === 0) {
                    $route = preg_replace('#static/#', '', $route, 1);
                    $_GET['resource'] = $route;
                    include($magentoPackagePubDir.'/static.php');
                    exit;
                } elseif (strpos($route, 'media/') === 0) {
                    include($magentoPackagePubDir.'/get.php');
                    exit;
                }
            }
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
        return $sitePath.'/pub/index.php';


    }
}