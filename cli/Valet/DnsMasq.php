<?php

namespace Valet;

use Exception;
use Symfony\Component\Process\Process;

class DnsMasq
{
    var $ubuntu, $cli, $files;

    var $configPath = '/etc/dnsmasq.conf';
    var $exampleConfigPath;

    /**
     * Create a new DnsMasq instance.
     *
     * @param  Ubuntu  $ubuntu
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    function __construct(Ubuntu $ubuntu, CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->ubuntu = $ubuntu;
        $this->files = $files;
        $this->exampleConfigPath = $this->files->get(__DIR__.'/../stubs/Caddyfile');
    }

    /**
     * Install and configure DnsMasq.
     *
     * @return void
     */
    function install($domain = 'dev')
    {
        $this->ubuntu->ensureInstalled('dnsmasq');
        $this->manageDnsmasqManually();

        // For DnsMasq, we create our own custom configuration file which will be imported
        // in the main DnsMasq file. This allows Valet to make changes to our own files
        // without needing to modify the "primary" DnsMasq configuration files again.
        $this->createCustomConfigFile($domain);

        // $this->createDomainResolver($domain);

        $this->ubuntu->restartService('dnsmasq');
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
     * Configure standalone Dnsmasq
     *
     * @return void
     */
    function manageDnsmasqManually()
    {
        // Because I don't want you to lose your network connection everytime we update the domain
        // lets remove the Dnsmasq control from NetworkManager.
        if ( $this->cli->run('grep \'^dns=dnsmasq\' /etc/NetworkManager/NetworkManager.conf') ) {
            $this->cli->run('sudo sed -i \'s/^dns=/#dns=/g\' /etc/NetworkManager/NetworkManager.conf');
            $this->cli->run('sudo service network-manager stop');
            $this->cli->run('sudo pkill dnsmasq');
            $this->cli->run('sudo service network-manager start');
            $this->cli->run('sudo service dnsmasq restart');
        }
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
     * Update the domain used by DnsMasq.
     *
     * @param  string  $oldDomain
     * @param  string  $newDomain
     * @return void
     */
    function updateDomain($oldDomain, $newDomain)
    {
        // $this->files->unlink($this->resolverPath.'/'.$oldDomain);

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
