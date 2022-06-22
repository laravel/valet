<?php

class WordPressValetDriver extends BasicValetDriver
{
    /**
     * @var string real wordpress site path.
     */
    public $wpSitePath = '';

    /**
     * Determine is a WordPress site.
     *
     * @param  string  $path
     * @return bool
     */
    public function isWordPress($path)
    {
        return file_exists($path . '/wp-config.php') || file_exists($path . '/wp-config-sample.php');
    }

    /**
     * Find real wordpress site path
     *
     * @param  string  $sitePath
     * @param  string  $uri
     * @return string
     */
    public function wpSitePath($sitePath, $uri)
    {
        $uri = rtrim($uri, '/');

        if ($this->isWordPress($sitePath . $uri)) {
            return $sitePath . $uri;
        } elseif (substr_count($uri, '/') > 1) {
            $pos = strripos($uri, '/');
            $uri = substr($uri, 0, $pos);

            return $this->wpSitePath($sitePath, $uri);
        }

        return '';
    }

    /**
     * Get prepared wordpress uri
     *
     * @param  string  $sitePath
     * @param  string  $uri
     * @return string
     */
    public function wpUri($sitePath, $uri)
    {
        $wpSitePath = $this->wpSitePath;
        $replace = str_replace($wpSitePath, '', $sitePath . $uri);

        return is_string($replace) ? $replace : '';
    }

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
        $this->wpSitePath = $this->wpSitePath($sitePath, $uri);

        return !empty($this->wpSitePath);
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
        $_SERVER['PHP_SELF'] = $uri;
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];

        $wpSitePath = $this->wpSitePath;
        $wpUri = $this->wpUri($sitePath, $uri);

        return parent::frontControllerPath(
            $wpSitePath, $siteName, $this->forceTrailingSlash($wpUri)
        );
    }

    /**
     * Redirect to uri with trailing slash.
     *
     * @param  string  $uri
     * @return string
     */
    private function forceTrailingSlash($uri)
    {
        if (substr($uri, -1 * strlen('/wp-admin')) == '/wp-admin') {
            header('Location: '.$uri.'/');
            exit;
        }

        return $uri;
    }
}
