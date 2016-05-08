<?php

namespace Valet;

class Configuration
{
    var $files;

    /**
     * Create a new Valet configuration class instance.
     *
     * @param  Filesystem  $filesystem
     * @return void
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
        if (! $this->files->isDir(VALET_HOME_PATH)) {
            $this->files->mkdirAsUser(VALET_HOME_PATH);
        }
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
     * @return array
     */
    function addPath($path)
    {
        $this->write(tap($this->read(), function (&$config) use ($path) {
            $config['paths'] = collect($config['paths'])->push($path)->unique()->all();
        }));
    }

    /**
     * Add the given path to the configuration.
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
    function write(array $config)
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
