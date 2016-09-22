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
    | All of these patterns won't be publicly available from your web server.
    | Instead, the server will throw a 403 forbidden response, if you try
    | to access these files via the HTTP layer. Use regex syntax here.
    |
    */
    protected $forbiddenUriPatterns = [
        '_(recycler|temp)_/',
        '^/(typo3conf/ext|typo3/sysext|typo3/ext)/[^/]+/(Resources/Private|Tests)/',
        '\.(htaccess|gitkeep|gitignore)',
    ];

    /**
     * Determine if the driver serves the request. For TYPO3, this is the
     * case, if a folder called "typo3" is present in the document root.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
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
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        $uri = $this->isVersionNumberInFilename($sitePath . $this->documentRoot . $uri, $uri);

        if (file_exists($filePath = $sitePath . $this->documentRoot . $uri)
            && ! is_dir($filePath)
            && pathinfo($filePath)['extension'] !== 'php')
        {
            $this->authorizeAccess($uri);
            return $filePath;
        }
        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     * This can be the currently requested PHP script, a folder that
     * contains an index.php or the global index.php otherwise.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        $this->directLoginToInstallTool($uri);
        $this->authorizeAccess($uri);
        $uri = rtrim($uri, '/');
        $absoluteFilePath = $sitePath . $this->documentRoot . $uri;

        if (file_exists($absoluteFilePath))
        {
            if (is_dir($absoluteFilePath))
            {
                if (file_exists($absoluteFilePath . '/index.php'))
                {
                    // this folder can be served by index.php
                    return $this->serveScript($sitePath, $siteName, $uri . '/index.php');
                }

                if (file_exists($absoluteFilePath . '/index.html'))
                {
                    // this folder can be served by index.html
                    return $absoluteFilePath . '/index.html';
                }
            }
            else if (pathinfo($absoluteFilePath)['extension'] === 'php')
            {
                // this file can be served directly
                return $this->serveScript($sitePath, $siteName, $uri);
            }
        }

        // the global index.php will handle all other cases
        return $this->serveScript($sitePath, $siteName, '/index.php');
    }

    /**
     * Configures the $_SERVER globals for serving the script at
     * the specified URI and returns it absolute file path.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string
     */
    private function serveScript($sitePath, $siteName, $uri)
    {
        $absoluteDocumentRoot = $sitePath . $this->documentRoot;
        $absoluteFilePath = $absoluteDocumentRoot . $uri;

        $_SERVER['SERVER_NAME'] = $siteName . '.dev';
        $_SERVER['DOCUMENT_ROOT'] = $absoluteDocumentRoot;
        $_SERVER['DOCUMENT_URI'] = $uri;
        $_SERVER['SCRIPT_FILENAME'] = $absoluteFilePath;
        $_SERVER['SCRIPT_NAME'] = $uri;
        $_SERVER['PHP_SELF'] = $uri;

        return $absoluteFilePath;
    }

    /**
     * Interrupts execution with a 403 FORBIDDEN if the requested URI is on
     * the global blacklist of system files that should not be served.
     *
     * @param string $uri
     */
    private function authorizeAccess($uri)
    {
        foreach ($this->forbiddenUriPatterns as $forbiddenUri)
        {
            if (preg_match("@$forbiddenUri@", $uri))
            {
                header('HTTP/1.0 403 Forbidden');
                die("You are forbidden to see $uri!");
            }
        }
    }


    /**
     * Rule for versioned static files, configured through:
     * - $GLOBALS['TYPO3_CONF_VARS']['BE']['versionNumberInFilename']
     * - $GLOBALS['TYPO3_CONF_VARS']['FE']['versionNumberInFilename']
     *
     * @param string $filePath
     * @param string $uri
     * @return string $uri
     */
    private function isVersionNumberInFilename($filePath, $uri) {
        if ( ! file_exists($filePath) &&
            preg_match("/^(.+)\.(\d+)\.(php|js|css|png|jpg|gif|gzip)$/", $uri)
        ) {
            return preg_replace("/^(.+)\.(\d+)\.(php|js|css|png|jpg|gif|gzip)$/", "$1.$3", $uri);
        }

        return $uri;
    }

    /**
     * Direct access to installtool via domain.dev/typo3/install/
     * Will be redirected to the sysext install script
     *
     * @param string $uri
     */
    private function directLoginToInstallTool($uri) {
        if (preg_match("/^\/typo3\/install$/", rtrim($uri)))
        {
            header('Location: /typo3/sysext/install/Start/Install.php');
            die();
        }
    }
}
