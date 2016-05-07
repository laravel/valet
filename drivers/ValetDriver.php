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

        $drivers[] = 'CraftValetDriver';
        $drivers[] = 'JigsawValetDriver';
        $drivers[] = 'SculpinValetDriver';
        $drivers[] = 'StatamicValetDriver';
        $drivers[] = 'SymfonyValetDriver';
        $drivers[] = 'WordPressValetDriver';
        $drivers[] = 'MagentoValetDriver';

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
        $extension = pathinfo($staticFilePath)['extension'];

        $mimes = require(__DIR__.'/../mimes.php');

        $mime = isset($mimes[$extension]) ? $mimes[$extension] : 'application/octet-stream';

        header('Content-Type: '. $mime);

        readfile($staticFilePath);
    }
}
