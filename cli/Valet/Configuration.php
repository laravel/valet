<?php

namespace Valet;

class Configuration
{
    var $files;

    /**
     * Create a new Valet configuration class instance.
     *
     * @param Filesystem $filesystem
     */
    function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Install the Valet configuration file.
     *
     * @return void
     */
    function install()
    {
        $this->createConfigurationDirectory();
        $this->createDriversDirectory();
        $this->createSitesDirectory();
        $this->createExtensionsDirectory();
        $this->createLogDirectory();
        $this->createCertificatesDirectory();
        $this->writeBaseConfiguration();

        $this->files->chown($this->path(), user());
    }

    /**
     * Create the Valet configuration directory.
     *
     * @return void
     */
    function createConfigurationDirectory()
    {
        $this->files->ensureDirExists(VALET_HOME_PATH, user());
    }

    /**
     * Create the Valet drivers directory.
     *
     * @return void
     */
    function createDriversDirectory()
    {
        if ($this->files->isDir($driversDirectory = VALET_HOME_PATH.'/Drivers')) {
            return;
        }

        $this->files->mkdirAsUser($driversDirectory);

        $this->files->putAsUser(
            $driversDirectory.'/SampleValetDriver.php',
            $this->files->get(__DIR__.'/../stubs/SampleValetDriver.php')
        );
    }

    /**
     * Create the Valet sites directory.
     *
     * @return void
     */
    function createSitesDirectory()
    {
        $this->files->ensureDirExists(VALET_HOME_PATH.'/Sites', user());
    }

    /**
     * Create the directory for the Valet extensions.
     *
     * @return void
     */
    function createExtensionsDirectory()
    {
        $this->files->ensureDirExists(VALET_HOME_PATH.'/Extensions', user());
    }

    /**
     * Create the directory for Nginx logs.
     *
     * @return void
     */
    function createLogDirectory()
    {
        $this->files->ensureDirExists(VALET_HOME_PATH.'/Log', user());

        $this->files->touch(VALET_HOME_PATH.'/Log/nginx-error.log');
    }

    /**
     * Create the directory for SSL certificates.
     *
     * @return void
     */
    function createCertificatesDirectory()
    {
        $this->files->ensureDirExists(VALET_HOME_PATH.'/Certificates', user());
    }

    /**
     * Write the base, initial configuration for Valet.
     */
    function writeBaseConfiguration()
    {
        if (! $this->files->exists($this->path())) {
            $this->write(['domain' => 'dev', 'paths' => []]);
        }
    }

    /**
     * Add the given path to the configuration.
     *
     * @param  string  $path
     * @param  bool  $prepend
     * @return void
     */
    function addPath($path, $prepend = false)
    {
        $this->write(tap($this->read(), function (&$config) use ($path, $prepend) {
            $method = $prepend ? 'prepend' : 'push';

            $config['paths'] = collect($config['paths'])->{$method}($path)->unique()->all();
        }));
    }

    /**
     * Prepend the given path to the configuration.
     *
     * @param  string  $path
     * @return void
     */
    function prependPath($path)
    {
        $this->addPath($path, true);
    }

    /**
     * Remove the given path from the configuration.
     *
     * @param  string  $path
     * @return void
     */
    function removePath($path)
    {
        $this->write(tap($this->read(), function (&$config) use ($path) {
            $config['paths'] = collect($config['paths'])->reject(function ($value) use ($path) {
                return $value === $path;
            })->values()->all();
        }));
    }

    /**
     * Prune all non-existent paths from the configuration.
     *
     * @return void
     */
    function prune()
    {
        if (! $this->files->exists($this->path())) {
            return;
        }

        $this->write(tap($this->read(), function (&$config) {
            $config['paths'] = collect($config['paths'])->filter(function ($path) {
                return $this->files->isDir($path);
            })->values()->all();
        }));
    }

    /**
     * Read the configuration file as JSON.
     *
     * @return array
     */
    function read()
    {
        return json_decode($this->files->get($this->path()), true);
    }

    /**
     * Update a specific key in the configuration file.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return array
     */
    function updateKey($key, $value)
    {
        return tap($this->read(), function (&$config) use ($key, $value) {
            $config[$key] = $value;

            $this->write($config);
        });
    }

    /**
     * Write the given configuration to disk.
     *
     * @param  array  $config
     * @return void
     */
    function write($config)
    {
        $this->files->putAsUser($this->path(), json_encode(
            $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ).PHP_EOL);
    }

    /**
     * Get the configuration file path.
     *
     * @return string
     */
    function path()
    {
        return VALET_HOME_PATH.'/config.json';
    }
}
