<?php

namespace Valet\Drivers;

class BasicValetDriver extends ValetDriver
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
        return true;
    }

    /**
     * Take any steps necessary before loading the front controller for this driver.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return void
     */
    public function beforeLoading(string $sitePath, string $siteName, string $uri): void
    {
        $_SERVER['PHP_SELF'] = $uri;
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string|false
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri): string|false
    {
        if (file_exists($staticFilePath = $sitePath.'/public'.rtrim($uri, '/').'/index.html')) {
            return $staticFilePath;
        } elseif (file_exists($staticFilePath = $sitePath.'/public'.rtrim($uri, '/').'/index.php')) {
            return $staticFilePath;
        } elseif (file_exists($staticFilePath = $sitePath.'/public'.$uri)) {
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
        $dynamicCandidates = [
            $this->asActualFile($sitePath, $uri),
            $this->asPhpIndexFileInDirectory($sitePath, $uri),
            $this->asHtmlIndexFileInDirectory($sitePath, $uri),
        ];

        foreach ($dynamicCandidates as $candidate) {
            if ($this->isActualFile($candidate)) {
                $_SERVER['SCRIPT_FILENAME'] = $candidate;
                $_SERVER['SCRIPT_NAME'] = str_replace($sitePath, '', $candidate);
                $_SERVER['DOCUMENT_ROOT'] = $sitePath;

                return $candidate;
            }
        }

        $fixedCandidatesAndDocroots = [
            $this->asRootPhpIndexFile($sitePath) => $sitePath,
            $this->asPublicPhpIndexFile($sitePath) => $sitePath.'/public',
            $this->asPublicHtmlIndexFile($sitePath) => $sitePath.'/public',
        ];

        foreach ($fixedCandidatesAndDocroots as $candidate => $docroot) {
            if ($this->isActualFile($candidate)) {
                $_SERVER['SCRIPT_FILENAME'] = $candidate;
                $_SERVER['SCRIPT_NAME'] = '/index.php';
                $_SERVER['DOCUMENT_ROOT'] = $docroot;

                return $candidate;
            }
        }
    }

    /**
     * Concatenate the site path and URI as a single file name.
     *
     * @param  string  $sitePath
     * @param  string  $uri
     * @return string
     */
    protected function asActualFile($sitePath, $uri)
    {
        return $sitePath.$uri;
    }

    /**
     * Format the site path and URI with a trailing "index.php".
     *
     * @param  string  $sitePath
     * @param  string  $uri
     * @return string
     */
    protected function asPhpIndexFileInDirectory($sitePath, $uri)
    {
        return $sitePath.rtrim($uri, '/').'/index.php';
    }

    /**
     * Format the site path and URI with a trailing "index.html".
     *
     * @param  string  $sitePath
     * @param  string  $uri
     * @return string
     */
    protected function asHtmlIndexFileInDirectory($sitePath, $uri)
    {
        return $sitePath.rtrim($uri, '/').'/index.html';
    }

    /**
     * Format the incoming site path as root "index.php" file path.
     *
     * @param  string  $sitePath
     * @return string
     */
    protected function asRootPhpIndexFile($sitePath)
    {
        return $sitePath.'/index.php';
    }

    /**
     * Format the incoming site path as a "public/index.php" file path.
     *
     * @param  string  $sitePath
     * @return string
     */
    protected function asPublicPhpIndexFile($sitePath)
    {
        return $sitePath.'/public/index.php';
    }

    /**
     * Format the incoming site path as a "public/index.php" file path.
     *
     * @param  string  $sitePath
     * @return string
     */
    protected function asPublicHtmlIndexFile($sitePath)
    {
        return $sitePath.'/public/index.html';
    }
}
