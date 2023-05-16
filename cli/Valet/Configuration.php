<?php

namespace Valet;

class Configuration
{
    public function __construct(public Filesystem $files)
    {
    }

    /**
     * Install the Valet configuration file.
     */
    public function install(): void
    {
        $this->createConfigurationDirectory();
        $this->createDriversDirectory();
        $this->createSitesDirectory();
        $this->createLogDirectory();
        $this->createCertificatesDirectory();
        $this->ensureBaseConfiguration();

        $this->files->chown($this->path(), user());
    }

    /**
     * Forcefully delete the Valet home configuration directory and contents.
     */
    public function uninstall(): void
    {
        $this->files->unlink(VALET_HOME_PATH);
    }

    /**
     * Create the Valet configuration directory.
     */
    public function createConfigurationDirectory(): void
    {
        $this->files->ensureDirExists(preg_replace('~/valet$~', '', VALET_HOME_PATH), user());
        $this->files->ensureDirExists(VALET_HOME_PATH, user());
    }

    /**
     * Create the Valet drivers directory.
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
     */
    public function createSitesDirectory(): void
    {
        $this->files->ensureDirExists(VALET_HOME_PATH.'/Sites', user());
    }

    /**
     * Create the directory for Nginx logs.
     */
    public function createLogDirectory(): void
    {
        $this->files->ensureDirExists(VALET_HOME_PATH.'/Log', user());

        $this->files->touch(VALET_HOME_PATH.'/Log/nginx-error.log');
    }

    /**
     * Create the directory for SSL certificates.
     */
    public function createCertificatesDirectory(): void
    {
        $this->files->ensureDirExists(VALET_HOME_PATH.'/Certificates', user());
    }

    /**
     * Ensure the base initial configuration has been installed.
     */
    public function ensureBaseConfiguration(): void
    {
        $this->writeBaseConfiguration();

        if (empty($this->read()['tld'])) {
            $this->updateKey('tld', 'test');
        }

        if (empty($this->read()['loopback'])) {
            $this->updateKey('loopback', '127.0.0.1');
        }
    }

    /**
     * Write the base initial configuration for Valet.
     */
    public function writeBaseConfiguration(): void
    {
        if (! $this->files->exists($this->path())) {
            $this->write(['tld' => 'test', 'loopback' => VALET_LOOPBACK, 'paths' => []]);
        }
    }

    /**
     * Add the given path to the configuration.
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
     */
    public function prependPath(string $path): void
    {
        $this->addPath($path, true);
    }

    /**
     * Remove the given path from the configuration.
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
     */
    public function read(): array
    {
        if (! $this->files->exists($this->path())) {
            return [];
        }

        return json_decode($this->files->get($this->path()), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Update a specific key in the configuration file.
     */
    public function updateKey(string $key, mixed $value): array
    {
        return tap($this->read(), function (&$config) use ($key, $value) {
            $config[$key] = $value;

            $this->write($config);
        });
    }

    /**
     * Write the given configuration to disk.
     */
    public function write(array $config): void
    {
        $this->files->putAsUser($this->path(), json_encode(
            $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ).PHP_EOL);
    }

    /**
     * Get the configuration file path.
     */
    public function path(): string
    {
        return VALET_HOME_PATH.'/config.json';
    }
}
