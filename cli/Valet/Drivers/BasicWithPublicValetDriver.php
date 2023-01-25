<?php

namespace Valet\Drivers;

class BasicWithPublicValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return is_dir($sitePath.'/public/');
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        $publicPath = $sitePath.'/public/'.trim($uri, '/');

        if ($this->isActualFile($publicPath)) {
            return $publicPath;
        } elseif (file_exists($publicPath.'/index.html')) {
            return $publicPath.'/index.html';
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        $_SERVER['PHP_SELF'] = $uri;
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];

        $docRoot = $sitePath.'/public';
        $uri = rtrim($uri, '/');

        $candidates = [
            $docRoot.$uri,
            $docRoot.$uri.'/index.php',
            $docRoot.'/index.php',
            $docRoot.'/index.html',
        ];

        foreach ($candidates as $candidate) {
            if ($this->isActualFile($candidate)) {
                $_SERVER['SCRIPT_FILENAME'] = $candidate;
                $_SERVER['SCRIPT_NAME'] = str_replace($sitePath.'/public', '', $candidate);
                $_SERVER['DOCUMENT_ROOT'] = $sitePath.'/public';

                return $candidate;
            }
        }

        return null;
    }
}
