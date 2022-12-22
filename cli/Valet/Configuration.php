<?php

namespace Valet;

class Configuration
{
    /**
     * Create a new Valet configuration class instance.
     *
     * @param  Filesystem  $files
     */
    public function __construct(public Filesystem $files)
    {
    }

    /**
     * Install the Valet configuration file.
     *
     * @return void
     */
    public function install(): void
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
     * Forcefully delete the Valet home configuration directory and contents.
     *
     * @return void
     */
    public function uninstall(): void
    {
        $this->files->unlink(VALET_HOME_PATH);
    }

    /**
     * Create the Valet configuration directory.
     *
     * @return void
     */
    public function createConfigurationDirectory(): void
    {
        $this->files->ensureDirExists(preg_replace('~/valet$~', '', VALET_HOME_PATH), user());

        $oldPath = posix_getpwuid(fileowner(__FILE__))['dir'].'/.valet';

        if ($this->files->isDir($oldPath)) {
            rename($oldPath, VALET_HOME_PATH);
            $this->prependPath(VALET_HOME_PATH.'/Sites');
        }

        $this->files->ensureDirExists(VALET_HOME_PATH, user());
    }

    /**
     * Create the Valet drivers directory.
     *
     * @return void
     */
    public function createDriversDirectory(): void
    {
        if ($this->files->isDir($driversDirectory = VALET_HOME_PATH.'/Drivers')) {
            return;
        }

        $this->files->mkdirAsUser($driversDirectory);

        $this->files->putAsUser(
            $driversDirectory.'/SampleValetDriver.php',
            $this->files->getStub('SampleValetDriver.php')
        );
    }

    /**
     * Create the Valet sites directory.
     *
     * @return void
     */
    public function createSitesDirectory(): void
    {
        $this->files->ensureDirExists(VALET_HOME_PATH.'/Sites', user());
    }

    /**
     * Create the directory for the Valet extensions.
     *
     * @return void
     */
    public function createExtensionsDirectory(): void
    {
        $this->files->ensureDirExists(VALET_HOME_PATH.'/Extensions', user());
    }

    /**
     * Create the directory for Nginx logs.
     *
     * @return void
     */
    public function createLogDirectory(): void
    {
        $this->files->ensureDirExists(VALET_HOME_PATH.'/Log', user());

        $this->files->touch(VALET_HOME_PATH.'/Log/nginx-error.log');
    }

    /**
     * Create the directory for SSL certificates.
     *
     * @return void
     */
    public function createCertificatesDirectory(): void
    {
        $this->files->ensureDirExists(VALET_HOME_PATH.'/Certificates', user());
    }

    /**
     * Write the base, initial configuration for Valet.
     *
     * @return void
     */
    public function writeBaseConfiguration(): void
    {
        if (! $this->files->exists($this->path())) {
            $this->write(['tld' => 'test', 'loopback' => VALET_LOOPBACK, 'paths' => []]);
        }

        /**
         * Migrate old configurations from 'domain' to 'tld'.
         */
        $config = $this->read();

        if (! isset($config['tld'])) {
            $this->updateKey('tld', ! empty($config['domain']) ? $config['domain'] : 'test');
        }

        if (! isset($config['loopback'])) {
            $this->updateKey('loopback', VALET_LOOPBACK);
        }
    }

    /**
     * Add the given path to the configuration.
     *
     * @param  string  $path
     * @param  bool  $prepend
     * @return void
     */
    public function addPath(string $path, bool $prepend = false): void
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
    public function prependPath(string $path): void
    {
        $this->addPath($path, true);
    }

    /**
     * Remove the given path from the configuration.
     *
     * @param  string  $path
     * @return void
     */
    public function removePath(string $path): void
    {
        if ($path == VALET_HOME_PATH.'/Sites') {
            info('Cannot remove this directory because this is where Valet stores its site definitions.');
            info('Run [valet paths] for a list of parked paths.');
            exit();
        }

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
    public function prune(): void
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
    public function read(): array
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
    public function updateKey(string $key, $value): array
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
    public function write(array $config): void
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
    public function path(): string
    {
        return VALET_HOME_PATH.'/config.json';
    }
}
