<?php
class StaticValetDriver extends ValetDriver
{
    /**
     * The file directories to search.
     *
     * @var array
     */
    protected $buildFolders = ['build', 'dist', 'public'];

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
        if ($this->exists($sitePath, '/index.html')) {
            return true;
        }

        return false;
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
        if ($staticFilePath = $this->path($sitePath, $uri)) {
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
        return $this->path($sitePath, '/index.html');
    }

    /**
     * Returns true if file exists in any of the build folders.
     * 
     * @param  string  $sitePath
     * @param  string  $uri
     * @return bool
     */
    protected function exists($sitePath, $uri)
    {
        return !! $this->path($sitePath, $uri);
    }

    /**
     * Returns the path of the first file found within the build
     * folders.
     * 
     * @param  string  $sitePath
     * @param  string  $uri
     * @return string|false
     */
    protected function path($sitePath, $uri)
    {
        $glob = $sitePath . '/{'. implode(',', $this->buildFolders) . '}' . $uri;
        $files = glob($glob, GLOB_BRACE);

        return $files[0] ?? false;
    }
}
