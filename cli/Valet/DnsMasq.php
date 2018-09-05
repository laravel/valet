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
    function install($tld = 'test')
    {
        $this->brew->ensureInstalled('dnsmasq');

        // For DnsMasq, we create our own custom configuration file which will be imported
        // in the main DnsMasq file. This allows Valet to make changes to our own files
        // without needing to modify the "primary" DnsMasq configuration files again.
        $this->createCustomConfigFile($tld);

        $this->createTldResolver($tld);

        $this->brew->restartService('dnsmasq');

        info('Valet is configured to serve for TLD [.'.$tld.']');
    }

    /**
     * Append the custom DnsMasq configuration file to the main configuration file.
     *
     * @param  string  $tld
     * @return void
     */
    function createCustomConfigFile($tld)
    {
        $customConfigPath = $this->customConfigPath();

        $this->copyExampleConfig();

        $this->appendCustomConfigImport($customConfigPath);

        $this->files->putAsUser($customConfigPath, 'address=/.'.$tld.'/127.0.0.1'.PHP_EOL.'listen-address=127.0.0.1'.PHP_EOL);
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
        $contents = preg_replace('/^conf-file=.*\/\.valet\/.*$/m', '', $this->files->get($this->configPath));
        $this->files->putAsUser($this->configPath, $contents);

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
     * Create the resolver file to point the configured TLD to 127.0.0.1.
     *
     * @param  string  $tld
     * @return void
     */
    function createTldResolver($tld)
    {
        $this->files->ensureDirExists($this->resolverPath);

        $this->files->put($this->resolverPath.'/'.$tld, 'nameserver 127.0.0.1'.PHP_EOL);
    }

    /**
     * Update the TLD/domain resolved by DnsMasq.
     *
     * @param  string  $oldTld
     * @param  string  $newTld
     * @return void
     */
    function updateTld($oldTld, $newTld)
    {
        $this->files->unlink($this->resolverPath.'/'.$oldTld);

        $this->install($newTld);
    }

    /**
     * Get the custom configuration path.
     *
     * @return string
     */
    function customConfigPath()
    {
        return $_SERVER['HOME'].'/.config/valet/dnsmasq.conf';
    }
}
