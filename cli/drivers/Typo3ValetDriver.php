<?php

/**
 * This driver serves TYPO3 instances (version 7.0 and up). It activates, if it
 * finds the characteristic typo3/ folder in the document root, serves both
 * frontend and backend scripts and prevents access to private resources.
 */
class Typo3ValetDriver extends ValetDriver
{
    /*
    |--------------------------------------------------------------------------
    | Document Root Subdirectory
    |--------------------------------------------------------------------------
    |
    | This subdirectory contains the public server resources, such as the
    | index.php, the typo3 and fileadmin system directories. Change it
    | to '', if you don't use a subdirectory but valet link directly.
    |
    */
    protected $documentRoot = '/web';

    /*
    |--------------------------------------------------------------------------
    | Forbidden URI Patterns
    |--------------------------------------------------------------------------
    |
    | All of these patterns won't be accessible from your web server. Instead,
    | the server will throw a 403 forbidden response, if you try to access
    | these files via the HTTP layer. Use regex syntax here and escape @.
    |
    */
    protected $forbiddenUriPatterns = [
        '_(recycler|temp)_/',
        '^/(typo3conf/ext|typo3/sysext|typo3/ext)/[^/]+/(Resources/Private|Tests)/',
        '^/typo3/.+\.map$',
        '^/typo3temp/var/',
        '\.(htaccess|gitkeep|gitignore)$',
    ];

    /**
     * Determine if the driver serves the request. For TYPO3, this is the
     * case, if a folder called "typo3" is present in the document root.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     * @return bool
     */
    public function serves($sitePath, $siteName, $uri)
    {
        $typo3Dir = $sitePath . $this->documentRoot . '/typo3';

        return file_exists($typo3Dir) && is_dir($typo3Dir);
    }

    /**
     * Determine if the incoming request is for a static file. That is, it is
     * no PHP script file and the URI points to a valid file (no folder) on
     * the disk. Access to those static files will be authorized.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        // May the file contains a cache busting version string like filename.12345678.css
        // If that is the case, the file cannot be found on disk, so remove the version
        // identifier before retrying below.
        if (!$this->isActualFile($filePath = $sitePath . $this->documentRoot . $uri)) {
            $uri = preg_replace("@^(.+)\.(\d+)\.(js|css|png|jpg|gif|gzip)$@", "$1.$3", $uri);
        }

        // Now that any possible version string is cleared from the filename, the resulting
        // URI should be a valid file on disc. So assemble the absolut file name with the
        // same schema as above and if it exists, authorize access and return its path.
        if ($this->isActualFile($filePath = $sitePath . $this->documentRoot . $uri)) {
            return $this->isAccessAuthorized($uri) ? $filePath : false;
        }

        // This file cannot be found in the current project and thus cannot be served.
        return false;
    }

    /**
     * Determines if the given URI is blacklisted so that access is prevented.
     *
     * @param string $uri
     * @return boolean
     */
    private function isAccessAuthorized($uri)
    {
        foreach ($this->forbiddenUriPatterns as $forbiddenUriPattern) {
            if (preg_match("@$forbiddenUriPattern@", $uri)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     * This can be the currently requested PHP script, a folder that
     * contains an index.php or the global index.php otherwise.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        // without modifying the URI, redirect if necessary
        $this->handleRedirectBackendShorthandUris($uri);

        // from now on, remove trailing / for convenience for all the following join operations
        $uri = rtrim($uri, '/');

        // try to find the responsible script file for the requested folder / script URI
        if (file_exists($absoluteFilePath = $sitePath . $this->documentRoot . $uri)) {
            if (is_dir($absoluteFilePath)) {
                if (file_exists($absoluteFilePath . '/index.php')) {
                    // this folder can be served by index.php
                    return $this->serveScript($sitePath, $siteName, $uri . '/index.php');
                }

                if (file_exists($absoluteFilePath . '/index.html')) {
                    // this folder can be served by index.html
                    return $absoluteFilePath . '/index.html';
                }
            } elseif (pathinfo($absoluteFilePath, PATHINFO_EXTENSION) === 'php') {
                // this file can be served directly
                return $this->serveScript($sitePath, $siteName, $uri);
            }
        }

        // the global index.php will handle all other cases
        return $this->serveScript($sitePath, $siteName, '/index.php');
    }

    /**
     * Direct access to installtool via domain.test/typo3/install/ will be redirected to
     * sysext install script. domain.test/typo3 will be redirected to /typo3/, because
     * the generated JavaScript URIs on the login screen would be broken on /typo3.
     *
     * @param string $uri
     */
    private function handleRedirectBackendShorthandUris($uri)
    {
        if (rtrim($uri, '/') === '/typo3/install') {
            header('Location: /typo3/sysext/install/Start/Install.php');
            die();
        }

        if ($uri === '/typo3') {
            header('Location: /typo3/');
            die();
        }
    }

    /**
     * Configures the $_SERVER globals for serving the script at
     * the specified URI and returns it absolute file path.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     * @param string $script
     * @return string
     */
    private function serveScript($sitePath, $siteName, $uri)
    {
        $docroot = $sitePath . $this->documentRoot;
        $abspath = $docroot . $uri;

        $_SERVER['SERVER_NAME'] = $siteName . '.test';
        $_SERVER['DOCUMENT_ROOT'] = $docroot;
        $_SERVER['DOCUMENT_URI'] = $uri;
        $_SERVER['SCRIPT_FILENAME'] = $abspath;
        $_SERVER['SCRIPT_NAME'] = $uri;
        $_SERVER['PHP_SELF'] = $uri;

        return $abspath;
    }
}
