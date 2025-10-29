<?php

namespace Valet;

class DnsMasq
{
    public $dnsmasqMasterConfigFile = BREW_PREFIX . '/etc/dnsmasq.conf';

    public $dnsmasqSystemConfDir = BREW_PREFIX . '/etc/dnsmasq.d';

    public $resolverPath = '/etc/resolver';

    public function __construct(public Brew $brew, public CommandLine $cli, public Filesystem $files, public Configuration $configuration)
    {
    }

    /**
     * Install and configure DnsMasq.
     */
    public function install(string $tld = 'test'): void
    {
        $this->brew->ensureInstalled('dnsmasq');

        // For DnsMasq, we enable its feature of loading *.conf from /usr/local/etc/dnsmasq.d/
        // and then we put a valet config file in there to point to the user's home .config/valet/dnsmasq.d
        // This allows Valet to make changes to our own files without needing to modify the core dnsmasq configs
        $this->ensureUsingDnsmasqDForConfigs();

        $this->createTldResolver($tld);

        $this->brew->restartService('dnsmasq');

        info('Valet is configured to serve for TLD [.' . $tld . ']');
    }

    /**
     * Forcefully uninstall dnsmasq.
     */
    public function uninstall(): void
    {
        $this->brew->stopService('dnsmasq');
        $this->brew->uninstallFormula('dnsmasq');
        $this->cli->run('rm -rf ' . BREW_PREFIX . '/etc/dnsmasq.d/dnsmasq-valet.conf');

        // As Laravel Herd uses the same DnsMasq resolver, we should only
        // delete it if Herd is not installed.
        if (!$this->files->exists('/Applications/Herd.app')) {
            $tld = $this->configuration->read()['tld'];
            $this->files->unlink($this->resolverPath . '/' . $tld);
        }
    }

    /**
     * Stop the dnsmasq service.
     */
    public function stop(): void
    {
        $this->brew->stopService(['dnsmasq']);
    }

    /**
     * Tell Homebrew to restart dnsmasq.
     */
    public function restart(): void
    {
        $this->brew->restartService('dnsmasq');
    }

    /**
     * Ensure the DnsMasq configuration primary config is set to read custom configs.
     */
    public function ensureUsingDnsmasqDForConfigs(): void
    {
        info('Updating Dnsmasq configuration...');

        // set primary config to look for configs in /usr/local/etc/dnsmasq.d/*.conf
        $contents = $this->files->get($this->dnsmasqMasterConfigFile);
        // ensure the line we need to use is present, and uncomment it if needed
        if (strpos($contents, 'conf-dir=' . BREW_PREFIX . '/etc/dnsmasq.d/,*.conf') === false) {
            $contents .= PHP_EOL . 'conf-dir=' . BREW_PREFIX . '/etc/dnsmasq.d/,*.conf' . PHP_EOL;
        }
        $contents = str_replace('#conf-dir=' . BREW_PREFIX . '/etc/dnsmasq.d/,*.conf', 'conf-dir=' . BREW_PREFIX . '/etc/dnsmasq.d/,*.conf', $contents);

        // remove entries used by older Valet versions:
        $contents = preg_replace('/^conf-file.*valet.*$/m', '', $contents);

        // save the updated config file
        $this->files->put($this->dnsmasqMasterConfigFile, $contents);

        // remove old ~/.config/valet/dnsmasq.conf file because things are moved to the ~/.config/valet/dnsmasq.d/ folder now
        if (file_exists($file = dirname($this->dnsmasqUserConfigDir()) . '/dnsmasq.conf')) {
            unlink($file);
        }

        // add a valet-specific config file to point to user's home directory valet config
        $contents = $this->files->getStub('etc-dnsmasq-valet.conf');
        $contents = str_replace('VALET_HOME_PATH', VALET_HOME_PATH, $contents);
        $this->files->ensureDirExists($this->dnsmasqSystemConfDir, user());
        $this->files->putAsUser($this->dnsmasqSystemConfDir . '/dnsmasq-valet.conf', $contents);

        $this->files->ensureDirExists(VALET_HOME_PATH . '/dnsmasq.d', user());
    }

    /**
     * Create host-specific dnsmasq config (address=/fqdn/loopback).
     */
    public function createHostConfig(string $fqdn): void
    {
        $dir = $this->dnsmasqUserConfigDir();
        $this->files->ensureDirExists($dir, user());
        $loopback = $this->configuration->read()['loopback'];
        $file = $dir . 'host-' . $fqdn . '.conf';
        $contents = 'address=/' . $fqdn . '/' . $loopback . PHP_EOL . 'listen-address=' . $loopback . PHP_EOL;
        $this->files->putAsUser($file, $contents);
    }

    /**
     * Remove host-specific dnsmasq config.
     */
    public function deleteHostConfig(string $fqdn): void
    {
        $file = $this->dnsmasqUserConfigDir() . 'host-' . $fqdn . '.conf';
        if ($this->files->exists($file)) {
            $this->files->unlink($file);
        }
    }

    /**
     * Rename all host-*.conf files from old to new TLD + adjust content.
     */
    public function remapHostConfigs(string $oldTld, string $newTld): void
    {
        if ($oldTld === $newTld) {
            return;
        }

        $dir = $this->dnsmasqUserConfigDir();
        if (!$this->files->exists($dir)) {
            return;
        }

        foreach ($this->files->scandir($dir) as $file) {
            if (!str_starts_with($file, 'host-') || !str_ends_with($file, '.conf')) {
                continue;
            }

            $path = $dir . $file;
            $contents = $this->files->get($path);

            $fqdn = substr($file, 5, -5);

            if (!str_ends_with($fqdn, '.' . $oldTld)) {
                if (str_contains($contents, '/.' . $oldTld . '/')) {
                    $contents = str_replace('/.' . $oldTld . '/', '/.' . $newTld . '/', $contents);
                    $this->files->putAsUser($path, $contents);
                }
                continue;
            }

            $newFqdn = substr($fqdn, 0, -strlen('.'.$oldTld)).'.'.$newTld;
            $newContents = str_replace('/'.$fqdn.'/', '/'.$newFqdn.'/', $contents);
            $newContents = str_replace('/.'.$oldTld.'/', '/.'.$newTld.'/', $newContents);

            $newFile = 'host-'.$newFqdn.'.conf';
            $newPath = $dir.$newFile;

            $this->files->putAsUser($newPath, $newContents);
            $this->files->unlink($path);
        }
    }

    /**
     * Restart dnsmasq (after changes to *.conf).
     */
    public function reload(): void
    {
        $this->brew->restartService('dnsmasq');
    }

    /**
     * Create the resolver file to point the configured TLD to configured loopback address.
     */
    public function createTldResolver(string $tld): void
    {
        $this->files->ensureDirExists($this->resolverPath);
        $loopback = $this->configuration->read()['loopback'];

        $this->files->put($this->resolverPath . '/' . $tld, 'nameserver ' . $loopback . PHP_EOL);
    }

    /**
     * Update the TLD/domain resolved by DnsMasq.
     */
    public function updateTld(string $oldTld, string $newTld): void
    {
        $this->files->unlink($this->resolverPath . '/' . $oldTld);

        $legacy = $this->dnsmasqUserConfigDir().'tld-'.$oldTld.'.conf';
        if ($this->files->exists($legacy)) {
            $this->files->unlink($legacy);
        }

        $this->remapHostConfigs($oldTld, $newTld);

        $this->reload();
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
        return $_SERVER['HOME'] . '/.config/valet/dnsmasq.d/';
    }
}
