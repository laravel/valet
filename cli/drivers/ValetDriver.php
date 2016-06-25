<?php

abstract class ValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return bool
     */
    abstract public function serves($sitePath, $siteName, $uri);

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string|false
     */
    abstract public function isStaticFile($sitePath, $siteName, $uri);

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string
     */
    abstract public function frontControllerPath($sitePath, $siteName, $uri);

    /**
     * Find a driver that can serve the incoming request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return ValetDriver|null
     */
    public static function assign($sitePath, $siteName, $uri)
    {
        $drivers = static::driversIn(VALET_HOME_PATH.'/Drivers');

        $drivers[] = 'LaravelValetDriver';

        $drivers[] = 'WordPressValetDriver';
        $drivers[] = 'BedrockValetDriver';
        $drivers[] = 'SymfonyValetDriver';
        $drivers[] = 'CraftValetDriver';
        $drivers[] = 'StatamicValetDriver';
        $drivers[] = 'StatamicV1ValetDriver';
        $drivers[] = 'CakeValetDriver';
        $drivers[] = 'SculpinValetDriver';
        $drivers[] = 'JigsawValetDriver';
        $drivers[] = 'KirbyValetDriver';
        $drivers[] = 'ContaoValetDriver';
        $drivers[] = 'KatanaValetDriver';
        $drivers[] = 'JoomlaValetDriver';

        $drivers[] = 'BasicValetDriver';

        foreach ($drivers as $driver) {
            $driver = new $driver;

            if ($driver->serves($sitePath, $siteName, $driver->mutateUri($uri))) {
                return $driver;
            }
        }
    }

    /**
     * Get all of the driver classes in a given path.
     *
     * @param  string  $path
     * @return array
     */
    public static function driversIn($path)
    {
        if (! is_dir($path)) {
            return [];
        }

        $drivers = [];

        foreach (scandir($path) as $file) {
            if ($file !== 'ValetDriver.php' && strpos($file, 'ValetDriver') !== false) {
                require_once $path.'/'.$file;

                $drivers[] = basename($file, '.php');
            }
        }

        return $drivers;
    }

    /**
     * Mutate the incoming URI.
     *
     * @param  string  $uri
     * @return string
     */
    public function mutateUri($uri)
    {
        return $uri;
    }

    /**
     * Serve the static file at the given path.
     *
     * @param  string  $staticFilePath
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return void
     */
    public function serveStaticFile($staticFilePath, $sitePath, $siteName, $uri)
    {
        $extension = strtolower(pathinfo($staticFilePath)['extension']);

        $mimes = require(__DIR__.'/../mimes.php');

        $mime = isset($mimes[$extension]) ? $mimes[$extension] : 'application/octet-stream';

        header('Content-Type: '. $mime);

        readfile($staticFilePath);
    }

    /**
     * Determine if the path is a file and not a directory.
     *
     * @param  string  $path
     * @return bool
     */
    protected function isActualFile($path)
    {
        return ! is_dir($path) && file_exists($path);
    }
}
