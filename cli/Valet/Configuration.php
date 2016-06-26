<?php

namespace Valet;

class Configuration
{
    var $files;

    /**
     * Create a new Valet configuration class instance.
     *
     * @param Filesystem $files
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
     * Write the base, initial configuration for Valet.
     */
    function writeBaseConfiguration()
    {
        if ($this->files->exists($this->path()) && ! $this->isValid()) {
            // File already exists but of an older Valet version.
            // Re-write configuration file to add support for multiple domains.
            $this->write([
                'domains' => [
                    $this->read()
                ]
            ]);
        } else {
            // Write base configuration
            // with support for multiple domains.
            $this->write([
                'domains' => [
                    $this->generateNewDomainArray('dev')
                ]
            ]);
        }
    }

    /**
     * Get domain by name.
     *
     * @param  string  $domain
     * @return array
     */
    function getDomain($domain)
    {
        return collect($this->read()['domains'])->where('domain', $domain)->first();
    }

    /**
     * Get first registered domain name.
     *
     * @return array
     */
    function getFirstDomain()
    {
        $domains = collect($this->read()['domains']);

        return $domains->first();
    }

    /**
     * Get available domains by an array of domain names.
     *
     * @param  array $names
     * @return \Illuminate\Support\Collection
     */
    function getDomainsByNames($names)
    {
        return collect($this->read()['domains'])->reject(function ($domain) use ($names) {
            return ! in_array($domain['domain'], $names);
        });
    }

    /**
     * Get domains where path is registered.
     *
     * @param  string $path
     * @return \Illuminate\Support\Collection
     */
    function getDomainsByPath($path)
    {
        // Check which domains, path is registered with
        $domains = $this->getDomainsByParkedDirectory(dirname($path))->toArray();

        // Check which linked domains, path is registered with
        $linkedDomains = $this->getDomainsByLinkedDirectory(basename($path))->toArray();

        return collect()->merge($domains)->merge($linkedDomains)->unique();
    }

    /**
     * Get domains where directory is parked.
     *
     * @param  string $path
     * @return \Illuminate\Support\Collection
     */
    function getDomainsByParkedDirectory($path)
    {
        return collect($this->read()['domains'])->reject(function($domain) use ($path) {
            return ! in_array($path, $domain['paths']);
        });
    }

    /**
     * Get domains where path is a symbolic linked directory.
     *
     * @param  string $path
     * @return \Illuminate\Support\Collection
     */
    function getDomainsByLinkedDirectory($path)
    {
        return $this->getDomainsByNames(collect($this->files->scandir(VALET_HOME_PATH.'/Sites'))->reject(function($domain) use ($path) {
            $linkedSitesInDomain = $this->files->scandir(VALET_HOME_PATH.'/Sites/'.$domain);
            return ! in_array($path, $linkedSitesInDomain);
        })->all());
    }

    /**
     * Get all available domains.
     *
     * @return \Illuminate\Support\Collection
     */
    function getAllDomains()
    {
        return collect($this->read()['domains']);
    }

    /**
     * Update domain settings in the configuration file.
     *
     * @param  string $domain
     * @param  \Closure $callback
     * @return void
     */
    function updateConfigByDomain($domain, \Closure $callback)
    {
        $this->write(tap($this->read(), function (&$config) use ($domain, $callback) {
            foreach ($config['domains'] as $key => $data) {
                if ($data['domain'] != $domain) { continue; }

                call_user_func_array($callback, [&$config, $key, $data]);
                break;
            }
        }));
    }

    /**
     * Add new domain to configuration file.
     *
     * @param  string $domain
     * @return void
     */
    public function addDomain($domain)
    {
        $this->write(tap($this->read(), function (&$config) use ($domain) {
            $config['domains'][] = $this->generateNewDomainArray($domain);
        }));
    }

    /**
     * Delete domain from configuration file.
     *
     * @param  string $domain
     * @return void
     */
    function deleteDomain($domain)
    {
        $this->updateConfigByDomain($domain, function(&$config, $key, $value) use ($domain) {
            unset($config['domains'][$key]);
        });
    }

    /**
     * Rename existing domain in configuration file.
     *
     * @param  string $oldDomain
     * @param  string $newDomain
     * @return void
     */
    public function renameDomain($oldDomain, $newDomain)
    {
        $this->updateConfigByDomain($oldDomain, function(&$config, $key, $value) use ($newDomain) {
            $config['domains'][$key]['domain'] = $newDomain;
        });
    }

    /**
     * Add the given path to the configuration.
     *
     * @param  string  $domain
     * @param  string  $path
     * @param  bool    $prepend
     * @return void
     */
    function addPath($domain, $path, $prepend = false)
    {
        $method = $prepend ? 'prepend' : 'push';

        $this->updateConfigByDomain($domain, function(&$config, $key, $value) use ($domain, $path, $method) {
            $config['domains'][$key]['paths'] = collect($value['paths'])->{$method}($path)->unique()->all();
        });
    }

    /**
     * Prepend the given path to domain in the configuration file.
     *
     * @param  string  $domain
     * @param  string  $path
     * @return void
     */
    function prependPath($domain, $path)
    {
        $this->addPath($domain, $path, true);
    }

    /**
     * Remove the given path from domain in the configuration file.
     *
     * @param  string  $domain
     * @param  string  $path
     * @return void
     */
    function removePath($domain, $path)
    {
        $this->updateConfigByDomain($domain, function(&$config, $key, $value) use ($domain, $path) {
            $config['domains'][$key]['paths'] = collect($value['paths'])->reject(function ($currentPath) use ($path) {
                return $currentPath === $path;
            })->values()->all();
        });
    }

    /**
     * Prune all non-existent paths from the configuration file.
     *
     * @return void
     */
    function prune()
    {
        if (! $this->files->exists($this->path()) || ! $this->isValid()) {
            return;
        }

        $this->write(tap($this->read(), function (&$config) {
            foreach ($config['domains'] as $key => $data) {
                $config['domains'][$key]['paths'] = collect($data['paths'])->filter(function ($path) {
                    return $this->files->isDir($path);
                })->values()->all();
            }
        }));
    }

    /**
     * Check if domain exists.
     *
     * @param  string  $domain
     * @return bool
     */
    function doesDomainExist($domain)
    {
        return collect($this->read()['domains'])->contains('domain', $domain);
    }

    /**
     * Check if path is registered with domain.
     *
     * @param  string  $domain
     * @param  string  $path
     * @return bool
     */
    function doesDomainContainPath($domain, $path)
    {
        if (! $this->doesDomainExist($domain)) {
            return false;
        }

        foreach ($this->read()['domains'] as $key => $data) {
            if ($data['domain'] != $domain) { continue; }

            return in_array($path, $data['paths']);
        }
    }

    /**
     * Get total number of registered domains.
     *
     * @return int
     */
    function totalDomains()
    {
        return count($this->read()['domains']);
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
     * Generate new domain array.
     *
     * @param  string $domain
     * @return array
     */
    function generateNewDomainArray($domain)
    {
        return [
            'domain' => trim($domain, '.'),
            'paths' => []
        ];
    }

    /**
     * Check if config file is valid
     *
     * This method is used to determine if certain actions shouldn't be executed
     * since they would fail, if config file is of an older (invalid) format.
     *
     * @return bool
     */
    function isValid()
    {
        return array_key_exists('domains', $this->read());
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
