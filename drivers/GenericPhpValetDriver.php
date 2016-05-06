<?php

class GenericPhpValetDriver extends ValetDriver
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
        return file_exists($sitePath.'/public/index.php') ||
               file_exists($sitePath.'/index.php');
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
        if (file_exists($staticFilePath = $sitePath.'/public'.$uri)) {
            return $staticFilePath;
        } elseif (file_exists($staticFilePath = $sitePath.$uri) && ! is_dir($staticFilePath)) {
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
        $candidates = [
            $this->asActualFile($sitePath, $uri),
            $this->asIndexFileInDirectory($sitePath, $uri),
        ];

        foreach ($candidates as $candidate) {
            if ($this->isActualFile($candidate)) {
                $_SERVER['SCRIPT_FILENAME'] = $candidate;
                $_SERVER['SCRIPT_NAME'] = str_replace($sitePath, '', $candidate);
                return $candidate;
            }
        }

        $candidates = [
            $this->asPublicIndexFile($sitePath, $uri),
            // ...and other possible public prefixes
        ];

        foreach ($candidates as $candidate) {
            if ($this->isActualFile($candidate)) {
                $_SERVER['SCRIPT_FILENAME'] = $candidate;
                $_SERVER['SCRIPT_NAME'] = '/index.php';
                return $candidate;
            }
        }
    }

    protected function isActualFile($path)
    {
        return file_exists($path) && ! is_dir($path);
    }

    protected function asActualFile($sitePath, $uri)
    {
        return $sitePath.$uri;
    }

    protected function asIndexFileInDirectory($sitePath, $uri)
    {
        return $sitePath.rtrim($uri, '/').'/index.php';
    }

    protected function asPublicIndexFile($sitePath, $uri)
    {
        return $sitePath.'/public/index.php';
    }
}
