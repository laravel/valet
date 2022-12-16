<?php

namespace Valet\Drivers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Throwable;

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
    abstract public function serves(string $sitePath, string $siteName, string $uri): bool;

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string|false
     */
    abstract public function isStaticFile(string $sitePath, string $siteName, string $uri): string|false;

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string
     */
    abstract public function frontControllerPath(string $sitePath, string $siteName, string $uri): string;

    /**
     * Find a driver that can serve the incoming request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return ValetDriver|null
     */
    public static function assign(string $sitePath, string $siteName, string $uri): ?ValetDriver
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
        $drivers[] = 'Typo3ValetDriver';
        $drivers[] = 'NeosValetDriver';
        $drivers[] = 'Magento2ValetDriver';

        $drivers[] = 'BasicValetDriver';

        foreach ($drivers as $driver) {
            try {
                // Try for old, un-namespaced drivers
                $driver = new $driver;
            } catch (Throwable $e) {
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
     *
     * @param  string  $sitePath
     * @return string|null
     */
    public static function customSiteDriver(string $sitePath): ?string
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
     * Mutate the incoming URI.
     *
     * @param  string  $uri
     * @return string
     */
    public function mutateUri(string $uri): string
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
     *
     * @param  string  $path
     * @return bool
     */
    protected function isActualFile($path)
    {
        return ! is_dir($path) && file_exists($path);
    }

    /**
     * Load server environment variables if available.
     * Processes any '*' entries first, and then adds site-specific entries.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @return void
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
}
