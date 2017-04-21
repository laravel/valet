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
        $drivers = [];

        if ($customSiteDriver = static::customSiteDriver($sitePath)) {
            $drivers[] = $customSiteDriver;
        }

        $drivers = array_merge($drivers, static::driversIn(VALET_HOME_PATH.'/Drivers'));

        $drivers[] = 'LaravelValetDriver';

        $drivers[] = 'WordPressValetDriver';
        $drivers[] = 'BedrockValetDriver';
        $drivers[] = 'ContaoValetDriver';
        $drivers[] = 'SymfonyValetDriver';
        $drivers[] = 'CraftValetDriver';
        $drivers[] = 'StatamicValetDriver';
        $drivers[] = 'StatamicV1ValetDriver';
        $drivers[] = 'CakeValetDriver';
        $drivers[] = 'SculpinValetDriver';
        $drivers[] = 'JigsawValetDriver';
        $drivers[] = 'KirbyValetDriver';
        $drivers[] = 'KatanaValetDriver';
        $drivers[] = 'JoomlaValetDriver';
        $drivers[] = 'DrupalValetDriver';
        $drivers[] = 'Concrete5ValetDriver';
        $drivers[] = 'Magento2ValetDriver';

        $drivers[] = 'BasicValetDriver';

        foreach ($drivers as $driver) {
            $driver = new $driver;

            if ($driver->serves($sitePath, $siteName, $driver->mutateUri($uri))) {
                return $driver;
            }
        }
    }

    /**
     * Get the custom driver class from the site path, if one exists.
     *
     * @param  string  $sitePath
     * @return string
     */
    public static function customSiteDriver($sitePath)
    {
        if (! file_exists($sitePath.'/LocalValetDriver.php')) {
            return;
        }

        require_once $sitePath.'/LocalValetDriver.php';

        return 'LocalValetDriver';
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
        /**
         * Back story...
         *
         * PHP docs *claim* you can set default_mimetype = "" to disable the default
         * Content-Type header. This works in PHP 7+, but in PHP 5.* it sends an
         * *empty* Content-Type header, which is significantly different than
         * sending *no* Content-Type header.
         *
         * However, if you explicitly set a Content-Type header, then explicitly
         * remove that Content-Type header, PHP seems to not re-add the default.
         *
         * I have a hard time believing this is by design and not coincidence.
         *
         * Burn. it. all.
         */
        header('Content-Type: text/html');
        header_remove('Content-Type');

        header('X-Accel-Redirect: /' . VALET_STATIC_PREFIX . $staticFilePath);
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
