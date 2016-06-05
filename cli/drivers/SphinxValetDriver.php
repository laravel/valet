<?php
class SphinxValetDriver extends ValetDriver
{
    protected $detectFiles = [
        'source/conf.py',
        'make.bat',
        'Makefile'
    ];
    protected $index = '/build/html/index.html';
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
        foreach ($this->detectFiles as $file) {
            if (!file_exists($sitePath.'/'.$file)) {
                return false;
            }
        }
        return true;
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
        if (file_exists($staticFilePath = $sitePath.'/build/html/'.$uri)) {
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
        if ($uri === '/') {
            return $sitePath.$this->index;
        }
    }
}