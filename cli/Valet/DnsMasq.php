<?php

namespace Valet;

use Valet\Os\Installer;
use Valet\Os\Os;

class DnsMasq
{
    public string $dnsmasqMasterConfigFile;

    public string $dnsmasqSystemConfDir;

    public string $resolverPath = '/etc/resolver';

    public function __construct(public Installer $installer, public CommandLine $cli, public Filesystem $files, public Configuration $configuration)
    {
        $this->dnsmasqMasterConfigFile = $this->installer->os()->etcDir().'/dnsmasq.conf';
        $this->dnsmasqSystemConfDir = $this->installer->os()->etcDir().'/dnsmasq.d';
    }

    /**
     * Install and configure DnsMasq.
     */
    public function install(string $tld = 'test'): void
    {
        $this->installer->ensureInstalled('dnsmasq');

        if (Os::isLinux()) {
            $this->installer->disableSystemdResolve();
        }

        // For DnsMasq, we enable its feature of loading *.conf from (/usr/local)/etc/dnsmasq.d/
        // and then we put a valet config file in there to point to the user's home .config/valet/dnsmasq.d
        // This allows Valet to make changes to our own files without needing to modify the core dnsmasq configs
        $this->ensureUsingDnsmasqDForConfigs();

        $this->createDnsmasqTldConfigFile($tld);

        $this->createTldResolver($tld);

        $this->installer->restartService('dnsmasq');

        info('Valet is configured to serve for TLD [.'.$tld.']');
    }

    /**
     * Forcefully uninstall dnsmasq.
     */
    public function uninstall(): void
    {
        $this->installer->stopService('dnsmasq');
        $this->installer->uninstallFormula('dnsmasq');
        $this->cli->run('rm -rf '.$this->dnsmasqSystemConfDir.'/dnsmasq-valet.conf');
        $tld = $this->configuration->read()['tld'];
        $this->files->unlink($this->resolverPath.'/'.$tld);
    }

    /**
     * Tell Homebrew to restart dnsmasq.
     *
     * @return void
     */
    public function restart(): void
    {
        $this->installer->restartService('dnsmasq');
    }

    /**
     * Ensure the DnsMasq configuration primary config is set to read custom configs.
     */
    public function ensureUsingDnsmasqDForConfigs(): void
    {
        info('Updating Dnsmasq configuration...');

        // set primary config to look for configs in (/usr/local)/etc/dnsmasq.d/*.conf
        $contents = $this->files->get($this->dnsmasqMasterConfigFile);
        // ensure the line we need to use is present, and uncomment it if needed
        if (false === strpos($contents, 'conf-dir='.$this->dnsmasqSystemConfDir.'/,*.conf')) {
            $contents .= PHP_EOL.'conf-dir='.$this->dnsmasqSystemConfDir.'/,*.conf'.PHP_EOL;
        }
        $contents = str_replace('#conf-dir='.$this->dnsmasqSystemConfDir.'/,*.conf', 'conf-dir='.$this->dnsmasqSystemConfDir.'/,*.conf', $contents);

        // remove entries used by older Valet versions:
        $contents = preg_replace('/^conf-file.*valet.*$/m', '', $contents);

        // save the updated config file
        $this->files->put($this->dnsmasqMasterConfigFile, $contents);

        // remove old ~/.config/valet/dnsmasq.conf file because things are moved to the ~/.config/valet/dnsmasq.d/ folder now
        if (file_exists($file = dirname($this->dnsmasqUserConfigDir()).'/dnsmasq.conf')) {
            unlink($file);
        }

        // add a valet-specific config file to point to user's home directory valet config
        $contents = $this->files->getStub('etc-dnsmasq-valet.conf');
        $contents = str_replace('VALET_HOME_PATH', VALET_HOME_PATH, $contents);
        $this->files->ensureDirExists($this->dnsmasqSystemConfDir, user());
        $this->files->putAsUser($this->dnsmasqSystemConfDir.'/dnsmasq-valet.conf', $contents);

        $this->files->ensureDirExists(VALET_HOME_PATH.'/dnsmasq.d', user());
    }

    /**
     * Create the TLD-specific dnsmasq config file.
     */
    public function createDnsmasqTldConfigFile(string $tld): void
    {
        $tldConfigFile = $this->dnsmasqUserConfigDir().'tld-'.$tld.'.conf';
        $loopback = $this->configuration->read()['loopback'];

        $this->files->putAsUser($tldConfigFile, 'address=/.'.$tld.'/'.$loopback.PHP_EOL.'listen-address='.$loopback.PHP_EOL);
    }

    /**
     * Create the resolver file to point the configured TLD to configured loopback address.
     */
    public function createTldResolver(string $tld): void
    {
        if (Os::isLinux() && !$this->files->exists('/etc/resolv.conf')) {
            // @todo Is this right? Had to do this to get it even to *load* the internet after installing deleting systemd-resolve...
            $this->files->put('/etc/resolv.conf', 'nameserver 1.1.1.1', user());
        }

        // @todo: Can we keep this setup on Linux? Or will it require it to be in /etc/resolv.conf like all the simpler tutorials show? e.g.:
        // echo nameserver 8.8.8.8 | sudo tee /etc/resolv.conf
        $this->files->ensureDirExists($this->resolverPath);
        $loopback = $this->configuration->read()['loopback'];

        $this->files->put($this->resolverPath.'/'.$tld, 'nameserver '.$loopback.PHP_EOL);
    }

    /**
     * Update the TLD/domain resolved by DnsMasq.
     */
    public function updateTld(string $oldTld, string $newTld): void
    {
        $this->files->unlink($this->resolverPath.'/'.$oldTld);
        $this->files->unlink($this->dnsmasqUserConfigDir().'tld-'.$oldTld.'.conf');

        $this->install($newTld);
    }

    /**
     * Refresh the DnsMasq configuration.
     */
    public function refreshConfiguration(): void
    {
        $tld = $this->configuration->read()['tld'];

        $this->updateTld($tld, $tld);
    }

    /**
     * Get the custom configuration path.
     */
    public function dnsmasqUserConfigDir(): string
    {
        return $_SERVER['HOME'].'/.config/valet/dnsmasq.d/';
    }
}
