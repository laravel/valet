<?php

namespace Valet;

class DnsMasq
{
    public $brew;
    public $cli;
    public $files;
    public $configuration;

    public $dnsmasqMasterConfigFile = BREW_PREFIX.'/etc/dnsmasq.conf';
    public $dnsmasqSystemConfDir = BREW_PREFIX.'/etc/dnsmasq.d';
    public $resolverPath = '/etc/resolver';

    /**
     * Create a new DnsMasq instance.
     */
    public function __construct(Brew $brew, CommandLine $cli, Filesystem $files, Configuration $configuration)
    {
        $this->cli = $cli;
        $this->brew = $brew;
        $this->files = $files;
        $this->configuration = $configuration;
    }

    /**
     * Install and configure DnsMasq.
     *
     * @param  string  $tld
     * @return void
     */
    public function install(string $tld = 'test'): void
    {
        $this->brew->ensureInstalled('dnsmasq');

        // For DnsMasq, we enable its feature of loading *.conf from /usr/local/etc/dnsmasq.d/
        // and then we put a valet config file in there to point to the user's home .config/valet/dnsmasq.d
        // This allows Valet to make changes to our own files without needing to modify the core dnsmasq configs
        $this->ensureUsingDnsmasqDForConfigs();

        $this->createDnsmasqTldConfigFile($tld);

        $this->createTldResolver($tld);

        $this->brew->restartService('dnsmasq');

        info('Valet is configured to serve for TLD [.'.$tld.']');
    }

    /**
     * Forcefully uninstall dnsmasq.
     *
     * @return void
     */
    public function uninstall(): void
    {
        $this->brew->stopService('dnsmasq');
        $this->brew->uninstallFormula('dnsmasq');
        $this->cli->run('rm -rf '.BREW_PREFIX.'/etc/dnsmasq.d/dnsmasq-valet.conf');
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
        $this->brew->restartService('dnsmasq');
    }

    /**
     * Ensure the DnsMasq configuration primary config is set to read custom configs.
     *
     * @return void
     */
    public function ensureUsingDnsmasqDForConfigs(): void
    {
        info('Updating Dnsmasq configuration...');

        // set primary config to look for configs in /usr/local/etc/dnsmasq.d/*.conf
        $contents = $this->files->get($this->dnsmasqMasterConfigFile);
        // ensure the line we need to use is present, and uncomment it if needed
        if (false === strpos($contents, 'conf-dir='.BREW_PREFIX.'/etc/dnsmasq.d/,*.conf')) {
            $contents .= PHP_EOL.'conf-dir='.BREW_PREFIX.'/etc/dnsmasq.d/,*.conf'.PHP_EOL;
        }
        $contents = str_replace('#conf-dir='.BREW_PREFIX.'/etc/dnsmasq.d/,*.conf', 'conf-dir='.BREW_PREFIX.'/etc/dnsmasq.d/,*.conf', $contents);

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
     *
     * @param  string  $tld
     * @return void
     */
    public function createDnsmasqTldConfigFile(string $tld): void
    {
        $tldConfigFile = $this->dnsmasqUserConfigDir().'tld-'.$tld.'.conf';
        $loopback = $this->configuration->read()['loopback'];

        $this->files->putAsUser($tldConfigFile, 'address=/.'.$tld.'/'.$loopback.PHP_EOL.'listen-address='.$loopback.PHP_EOL);
    }

    /**
     * Create the resolver file to point the configured TLD to configured loopback address.
     *
     * @param  string  $tld
     * @return void
     */
    public function createTldResolver(string $tld): void
    {
        $this->files->ensureDirExists($this->resolverPath);
        $loopback = $this->configuration->read()['loopback'];

        $this->files->put($this->resolverPath.'/'.$tld, 'nameserver '.$loopback.PHP_EOL);
    }

    /**
     * Update the TLD/domain resolved by DnsMasq.
     *
     * @param  string  $oldTld
     * @param  string  $newTld
     * @return void
     */
    public function updateTld(string $oldTld, string $newTld): void
    {
        $this->files->unlink($this->resolverPath.'/'.$oldTld);
        $this->files->unlink($this->dnsmasqUserConfigDir().'tld-'.$oldTld.'.conf');

        $this->install($newTld);
    }

    /**
     * Refresh the DnsMasq configuration.
     *
     * @return void
     */
    public function refreshConfiguration(): void
    {
        $tld = $this->configuration->read()['tld'];

        $this->updateTld($tld, $tld);
    }

    /**
     * Get the custom configuration path.
     *
     * @return string
     */
    public function dnsmasqUserConfigDir(): string
    {
        return $_SERVER['HOME'].'/.config/valet/dnsmasq.d/';
    }
}
