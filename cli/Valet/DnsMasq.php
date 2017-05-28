<?php

namespace Valet;

use Valet\Contracts\PackageManager;
use Valet\Contracts\ServiceManager;

class DnsMasq
{
    public $pm;
    public $sm;
    public $cli;
    public $files;
    public $configPath;
    public $nmConfigPath;
    public $resolvedConfig;

    /**
     * Create a new DnsMasq instance.
     *
     * @param  PackageManager  $pm
     * @param  ServiceManager  $sm
     * @param  Filesystem  $files
     * @return void
     */
    public function __construct(PackageManager $pm, ServiceManager $sm, Filesystem $files, CommandLine $cli)
    {
        $this->pm = $pm;
        $this->sm = $sm;
        $this->cli = $cli;
        $this->files = $files;
        $this->configPath = '/etc/NetworkManager/dnsmasq.d/valet';
        $this->nmConfigPath = '/etc/NetworkManager/conf.d/valet.conf';
        $this->resolvedConfigPath = '/etc/systemd/resolved.conf';
    }

    /**
     * Install and configure DnsMasq.
     *
     * @return void
     */
    public function install()
    {
        $this->dnsmasqSetup();
        $this->fixResolved();
        $this->createCustomConfigFile('dev');
        $this->pm->dnsmasqRestart($this->sm);
    }

    /**
     * Append the custom DnsMasq configuration file to the main configuration file.
     *
     * @param  string  $domain
     * @return void
     */
    public function createCustomConfigFile($domain)
    {
        $this->files->putAsUser($this->configPath, 'address=/.'.$domain.'/127.0.0.1'.PHP_EOL);
    }

    /**
     * Fix systemd-resolved configuration.
     *
     * @return void
     */
    public function fixResolved()
    {
        $resolved = $this->resolvedConfigPath;

        $this->files->backup($resolved);
        $this->files->putAsUser($resolved, $this->files->get(__DIR__.'/../stubs/resolved.conf'));
    }

    /**
     * Setup dnsmasq with Network Manager.
     */
    public function dnsmasqSetup()
    {
        $this->pm->ensureInstalled('dnsmasq');
        $this->files->ensureDirExists('/etc/NetworkManager/conf.d');

        $this->files->putAsUser($this->nmConfigPath, $this->files->get(__DIR__.'/../stubs/networkmanager.conf'));
    }

    /**
     * Update the domain used by DnsMasq.
     *
     * @param  string  $newDomain
     * @return void
     */
    public function updateDomain($oldDomain, $newDomain)
    {
        $this->fixResolved();
        $this->createCustomConfigFile($newDomain);
        $this->pm->dnsmasqRestart($this->sm);
    }

    /**
     * Delete the DnsMasq config file.
     *
     * @return void
     */
    public function uninstall()
    {
        $this->files->unlink($this->configPath);
        $this->files->unlink($this->nmConfigPath);
        $this->files->restore($this->resolvedConfigPath);

        $this->pm->dnsmasqRestart($this->sm);
    }
}
