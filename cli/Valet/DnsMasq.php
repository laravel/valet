<?php

namespace Valet;

use Valet\Contracts\PackageManager;
use Valet\Contracts\ServiceManager;

class DnsMasq
{
    var $pm, $sm, $cli, $files, $configPath, $nmConfigPath;

    /**
     * Create a new DnsMasq instance.
     *
     * @param  PackageManager  $pm
     * @param  ServiceManager  $sm
     * @param  Filesystem  $files
     * @return void
     */
    function __construct(PackageManager $pm, ServiceManager $sm, Filesystem $files, CommandLine $cli)
    {
        $this->pm = $pm;
        $this->sm = $sm;
        $this->cli = $cli;
        $this->files = $files;
        $this->configPath = '/etc/NetworkManager/dnsmasq.d/valet';
        $this->nmConfigPath = '/etc/NetworkManager/NetworkManager.conf';
    }

    /**
     * Install and configure DnsMasq.
     *
     * @return void
     */
    function install($domain = 'dev')
    {
        $this->dnsmasqSetup();
        $this->createCustomConfigFile($domain);
        $this->pm->dnsmasqRestart($this->sm);
    }

    /**
     * Append the custom DnsMasq configuration file to the main configuration file.
     *
     * @param  string  $domain
     * @return void
     */
    function createCustomConfigFile($domain)
    {
        $this->files->put($this->configPath, 'address=/.'.$domain.'/127.0.0.1'.PHP_EOL);
    }

    /**
     * Setup dnsmasq with Network Manager.
     */
    function dnsmasqSetup()
    {
        $this->pm->ensureInstalled('dnsmasq');

        $conf = $this->nmConfigPath;

        $notInControlOfNetworkManager = empty($this->cli->run("grep '^dns=dnsmasq' $conf"));

        if ($notInControlOfNetworkManager) {
            $sed = "sed -i 's/#dns=/dns=/g' {$conf}";

            if (empty($this->cli->run("grep '#dns=dnsmasq' $conf"))) {
                $sed = "sed -i '/^\[main\]/adns=dnsmasq' {$conf}";
            }

            $this->cli->run($sed);
            $this->cli->run('pkill dnsmasq');
        }
    }

    /**
     * Update the domain used by DnsMasq.
     *
     * @param  string  $newDomain
     * @return void
     */
    function updateDomain($oldDomain, $newDomain)
    {
        $this->install($newDomain);
    }

    /**
     * Delete the DnsMasq config file.
     *
     * @return void
     */
    function uninstall()
    {
        if ($this->files->exists($this->configPath)) {
            $this->files->unlink($this->configPath);
            $this->pm->dnsmasqRestart($this->sm);
        }
    }
}
