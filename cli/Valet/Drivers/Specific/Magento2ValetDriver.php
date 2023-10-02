<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\ValetDriver;

class Magento2ValetDriver extends ValetDriver
{
    /**
     * {@inheritdoc}
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath.'/bin/magento') && file_exists($sitePath.'/pub/index.php');
    }

    /**
     * {@inheritdoc}
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        $uri = preg_replace('/^\/static(\/version[\d]+)/', '/static', $uri);

        if (file_exists($staticFilePath = $sitePath.'/pub'.$uri)) {
            return $staticFilePath;
        }

        if (strpos($uri, '/static/') === 0) {
            $_GET['resource'] = preg_replace('#static/#', '', $uri, 1);
            include $sitePath.'/pub/static.php';
            exit;
        }

        if (strpos($uri, '/media/') === 0) {
            include $sitePath.'/pub/get.php';
            exit;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
        $_SERVER['DOCUMENT_ROOT'] = $sitePath;

        return $sitePath.'/pub/index.php';
    }
}
