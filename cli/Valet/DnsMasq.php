<?php

namespace Valet;

use Exception;
use Symfony\Component\Process\Process;

class DnsMasq
{
    var $brew, $cli, $files;

    var $dnsmasqMasterConfigFile = '/usr/local/etc/dnsmasq.conf';
    var $dnsmasqSystemConfDir = '/usr/local/etc/dnsmasq.d';
    var $resolverPath = '/etc/resolver';

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
    function uninstall()
    {
        $this->brew->stopService('dnsmasq');
        $this->brew->uninstallFormula('dnsmasq');
        $this->cli->run('rm -rf /usr/local/etc/dnsmasq.d/dnsmasq-valet.conf');
    }

    /**
     * Tell Homebrew to restart dnsmasq
     * 
     * @return void
     */
    function restart()
    {
        $this->brew->restartService('dnsmasq');
    }

    /**
     * Ensure the DnsMasq configuration primary config is set to read custom configs
     *
     * @return void
     */
    function ensureUsingDnsmasqDForConfigs()
    {
        info('Updating Dnsmasq configuration...');

        // set primary config to look for configs in /usr/local/etc/dnsmasq.d/*.conf
        $contents = $this->files->get($this->dnsmasqMasterConfigFile);
        // ensure the line we need to use is present, and uncomment it if needed
        if (false === strpos($contents, 'conf-dir=/usr/local/etc/dnsmasq.d/,*.conf')) {
            $contents .= PHP_EOL . 'conf-dir=/usr/local/etc/dnsmasq.d/,*.conf' . PHP_EOL;
        }
        $contents = str_replace('#conf-dir=/usr/local/etc/dnsmasq.d/,*.conf', 'conf-dir=/usr/local/etc/dnsmasq.d/,*.conf', $contents);

        // remove entries used by older Valet versions:
        $contents = preg_replace('/^conf-file.*valet.*$/m', '', $contents);

        // save the updated config file
        $this->files->put($this->dnsmasqMasterConfigFile, $contents);

        // remove old ~/.config/valet/dnsmasq.conf file because things are moved to the ~/.config/valet/dnsmasq.d/ folder now
        if (file_exists($file = dirname($this->dnsmasqUserConfigDir()) . '/dnsmasq.conf')) {
            unlink($file);
        }

        // add a valet-specific config file to point to user's home directory valet config
        $contents = $this->files->get(__DIR__.'/../stubs/etc-dnsmasq-valet.conf');
        $contents = str_replace('VALET_HOME_PATH', VALET_HOME_PATH, $contents);
        $this->files->ensureDirExists($this->dnsmasqSystemConfDir, user());
        $this->files->putAsUser($this->dnsmasqSystemConfDir . '/dnsmasq-valet.conf', $contents);

        $this->files->ensureDirExists(VALET_HOME_PATH . '/dnsmasq.d', user());
    }

    /**
     * Create the TLD-specific dnsmasq config file
     * @param  string  $tld
     * @return void
     */
    function createDnsmasqTldConfigFile($tld)
    {
        $tldConfigFile = $this->dnsmasqUserConfigDir() . 'tld-' . $tld . '.conf';

        $this->files->putAsUser($tldConfigFile, 'address=/.'.$tld.'/127.0.0.1'.PHP_EOL.'listen-address=127.0.0.1'.PHP_EOL);
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
    function dnsmasqUserConfigDir()
    {
        return $_SERVER['HOME'].'/.config/valet/dnsmasq.d/';
    }
}
