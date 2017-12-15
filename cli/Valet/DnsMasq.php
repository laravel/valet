<?php

namespace Valet;

use Exception;
use Symfony\Component\Process\Process;

class DnsMasq
{
    var $brew, $cli, $files, $configuration;

    var $resolverPath = '/etc/resolver';
    var $configPath = '/usr/local/etc/dnsmasq.conf';
    var $exampleConfigPath = '/usr/local/opt/dnsmasq/dnsmasq.conf.example';

    /**
     * Create a new DnsMasq instance.
     *
     * @param  Brew  $brew
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @param  Configuration $configuration
     * @return void
     */
    function __construct(Brew $brew, CommandLine $cli, Filesystem $files, Configuration $configuration)
    {
        $this->cli = $cli;
        $this->brew = $brew;
        $this->files = $files;
        $this->configuration = $configuration;
    }

    /**
     * Install and configure DnsMasq.
     *
     * @return void
     */
    function install($domain = 'test')
    {
        $this->brew->ensureInstalled('dnsmasq');

        // For DnsMasq, we create our own custom configuration file which will be imported
        // in the main DnsMasq file. This allows Valet to make changes to our own files
        // without needing to modify the "primary" DnsMasq configuration files again.
        $this->createCustomConfigFile($domain);

        $this->appendListenAddressToConfigFile();

        $this->createDomainResolver($domain);

        $this->brew->restartService('dnsmasq');
    }

    /**
     * Append the custom DnsMasq configuration file to the main configuration file.
     *
     * @param  string  $domain
     * @return void
     */
    function createCustomConfigFile($domain)
    {
        $customConfigPath = $this->customConfigPath();

        $this->copyExampleConfig();

        $this->appendCustomConfigImport($customConfigPath);

        $this->files->appendAsUser($customConfigPath, 'address=/.'.$domain.'/127.0.0.1'.PHP_EOL);
    }

    /**
     * Append the DnsMasq listen-address configuration parameter.
     *
     * @return void
     */
    function appendListenAddressToConfigFile()
    {
        $listen = 'listen-address=127.0.0.1';
        $lines = collect(explode(PHP_EOL, $this->files->get($this->customConfigPath())))->filter()->reject(function ($line) use ($listen) {
            return $line === $listen;
        })->unique()->all();
        $lines[] = $listen;

        $this->files->putAsUser($this->customConfigPath(), implode(PHP_EOL, $lines) . PHP_EOL);
    }

    /**
     * Copy the Homebrew installed example DnsMasq configuration file.
     *
     * @return void
     */
    function copyExampleConfig()
    {
        if (! $this->files->exists($this->configPath)) {
            $this->files->copyAsUser(
                $this->exampleConfigPath,
                $this->configPath
            );
        }
    }

    /**
     * Append import command for our custom configuration to DnsMasq file.
     *
     * @param  string  $customConfigPath
     * @return void
     */
    function appendCustomConfigImport($customConfigPath)
    {
        if (! $this->customConfigIsBeingImported($customConfigPath)) {
            $this->files->appendAsUser(
                $this->configPath,
                PHP_EOL.'conf-file='.$customConfigPath.PHP_EOL
            );
        }
    }

    /**
     * Determine if Valet's custom DnsMasq configuration is being imported.
     *
     * @param  string  $customConfigPath
     * @return bool
     */
    function customConfigIsBeingImported($customConfigPath)
    {
        return strpos($this->files->get($this->configPath), $customConfigPath) !== false;
    }

    /**
     * Create the resolver file to point the configured domain to 127.0.0.1.
     *
     * @param  string  $domain
     * @return void
     */
    function createDomainResolver($domain)
    {
        $this->files->ensureDirExists($this->resolverPath);

        $this->files->put($this->resolverPath.'/'.$domain, 'nameserver 127.0.0.1'.PHP_EOL);
    }

    /**
     * Update the domain used by DnsMasq.
     *
     * @param  string  $oldDomain
     * @param  string  $newDomain
     * @return void
     */
    function updateDomain($oldDomain, $newDomain)
    {
        $this->files->unlink($this->resolverPath.'/'.$oldDomain);

        $this->install($newDomain);
        $this->updateCustomPathDomains();
    }

    function updateCustomPathDomains()
    {
        $paths = collect($this->configuration->read()['paths']);
        $paths->filter(function ($path) {
            return is_array($path);
        })->each(function ($path) {
            $this->createCustomConfigFile($path['domain']);

            $this->createDomainResolver($path['domain']);
        });

        if ($paths->isNotEmpty()) {
            $this->appendListenAddressToConfigFile();
        }
        $this->brew->restartService('dnsmasq');
    }

    /**
     * Get the custom configuration path.
     *
     * @return string
     */
    function customConfigPath()
    {
        return $_SERVER['HOME'].'/.valet/dnsmasq.conf';
    }
}
