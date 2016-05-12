<?php

namespace Valet;

use Exception;
use Symfony\Component\Process\Process;

class DnsMasq
{
    var $brew, $cli, $files;

    var $resolverPath = '/etc/resolver';
    var $configPath = '/usr/local/etc/dnsmasq.conf';
    var $exampleConfigPath = '/usr/local/opt/dnsmasq/dnsmasq.conf.example';

    /**
     * Create a new DnsMasq instance.
     *
     * @param  Brew  $brew
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    function __construct(Brew $brew, CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->brew = $brew;
        $this->files = $files;
    }

    /**
     * Install and configure DnsMasq.
     *
     * @return void
     */
    function install($domain = 'dev')
    {
        $this->brew->ensureInstalled('dnsmasq');

        // For DnsMasq, we create our own custom configuration file which will be imported
        // in the main DnsMasq file. This allows Valet to make changes to our own files
        // without needing to modify the "primary" DnsMasq configuration files again.
        $this->createCustomConfigFile($domain);

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

        $this->files->putAsUser($customConfigPath, 'address=/.'.$domain.'/127.0.0.1'.PHP_EOL);
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
     * Create the resolver file to point the "dev" domain to 127.0.0.1.
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
