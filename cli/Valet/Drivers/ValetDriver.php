<?php

namespace Valet\Drivers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

abstract class ValetDriver
{
    /**
     * Determine if the driver serves the request.
     */
    abstract public function serves(string $sitePath, string $siteName, string $uri): bool;

    /**
     * Determine if the incoming request is for a static file.
     */
    // While we support PHP 7.4 for individual site isolation...
    abstract public function isStaticFile(string $sitePath, string $siteName, string $uri);
    //abstract public function isStaticFile(string $sitePath, string $siteName, string $uri): string|false;

    /**
     * Get the fully resolved path to the application's front controller.
     */
    abstract public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string;

    /**
     * Find a driver that can serve the incoming request.
     */
    public static function assign(string $sitePath, string $siteName, string $uri): ?ValetDriver
    {
        $drivers = [];

        // Must scan these so they're extensible by customSiteDrivers loaded next
        $specificDrivers = static::specificDrivers();

        // Queue custom driver based on path
        if ($customSiteDriver = static::customSiteDriver($sitePath)) {
            $drivers[] = $customSiteDriver;
        }

        // Queue custom drivers for this environment
        $drivers = array_merge($drivers, static::customDrivers());

        // Queue Valet-shipped drivers
        $drivers[] = 'Specific\StatamicValetDriver';
        $drivers[] = 'LaravelValetDriver';
        $drivers = array_unique(array_merge($drivers, $specificDrivers));
        $drivers[] = 'BasicWithPublicValetDriver';
        $drivers[] = 'BasicValetDriver';

        foreach ($drivers as $driver) {
            if ($driver === 'LocalValetDriver') {
                $driver = new $driver;
            } else {
                $className = "Valet\Drivers\\{$driver}";
                $driver = new $className;
            }

            if ($driver->serves($sitePath, $siteName, $driver->mutateUri($uri))) {
                return $driver;
            }
        }
    }

    /**
     * Get the custom driver class from the site path, if one exists.
     */
    public static function customSiteDriver(string $sitePath): ?string
    {
        if (! file_exists($sitePath.'/LocalValetDriver.php')) {
            return null;
        }

        require_once $sitePath.'/LocalValetDriver.php';

        return 'LocalValetDriver';
    }

    /**
     * Get all of the driver classes in a given path.
     */
    public static function driversIn(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $drivers = [];

        $dir = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($dir);
        $regex = new RegexIterator($iterator, '/^.+ValetDriver\.php$/i', RecursiveRegexIterator::GET_MATCH);

        foreach ($regex as $file) {
            require_once $file[0];

            $drivers[] = basename($file[0], '.php');
        }

        return $drivers;
    }

    /**
     * Get all of the specific drivers shipped with Valet.
     */
    public static function specificDrivers(): array
    {
        return array_map(function ($item) {
            return 'Specific\\'.$item;
        }, static::driversIn(__DIR__.'/Specific'));
    }

    /**
     * Get all of the custom drivers defined by the user locally.
     */
    public static function customDrivers(): array
    {
        return array_map(function ($item) {
            return 'Custom\\'.$item;
        }, static::driversIn(VALET_HOME_PATH.'/Drivers'));
    }

    /**
     * Take any steps necessary before loading the front controller for this driver.
     */
    public function beforeLoading(string $sitePath, string $siteName, string $uri): void
    {
        // Do nothing
    }

    /**
     * Mutate the incoming URI.
     */
    public function mutateUri(string $uri): string
    {
        return $uri;
    }

    /**
     * Serve the static file at the given path.
     */
    public function serveStaticFile(string $staticFilePath, string $sitePath, string $siteName, string $uri): void
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

        header('X-Accel-Redirect: /'.VALET_STATIC_PREFIX.$staticFilePath);
    }

    /**
     * Determine if the path is a file and not a directory.
     */
    protected function isActualFile(string $path): bool
    {
        return ! is_dir($path) && file_exists($path);
    }

    /**
     * Load server environment variables if available.
     * Processes any '*' entries first, and then adds site-specific entries.
     */
    public function loadServerEnvironmentVariables(string $sitePath, string $siteName): void
    {
        $varFilePath = $sitePath.'/.valet-env.php';
        if (! file_exists($varFilePath)) {
            $varFilePath = VALET_HOME_PATH.'/.valet-env.php';
        }
        if (! file_exists($varFilePath)) {
            return;
        }

        $variables = include $varFilePath;

        $variablesToSet = isset($variables['*']) ? $variables['*'] : [];

        if (isset($variables[$siteName])) {
            $variablesToSet = array_merge($variablesToSet, $variables[$siteName]);
        }

        foreach ($variablesToSet as $key => $value) {
            if (! is_string($key)) {
                continue;
            }
            $_SERVER[$key] = $value;
            $_ENV[$key] = $value;
            putenv($key.'='.$value);
        }
    }

    public function composerRequires(string $sitePath, string $namespacedPackage): bool
    {
        if (! file_exists($sitePath.'/composer.json')) {
            return false;
        }

        $composer_json_source = file_get_contents($sitePath.'/composer.json');
        $composer_json = json_decode($composer_json_source, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return isset($composer_json['require'][$namespacedPackage]);
    }
}
