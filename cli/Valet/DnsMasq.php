<?php

namespace Valet;

use Exception;
use Symfony\Component\Process\Process;

class DnsMasq
{
    var $brew, $cli, $files, $config, $site;

    var $resolverPath = '/etc/resolver';
    var $configPath = '/usr/local/etc/dnsmasq.conf';
    var $exampleConfigPath = '/usr/local/opt/dnsmasq/dnsmasq.conf.example';

    /**
     * Create a new DnsMasq instance.
     *
     * @param  Brew $brew
     * @param  CommandLine $cli
     * @param  Filesystem $files
     * @param Configuration $config
     * @param Site $site
     */
    function __construct(Brew $brew, CommandLine $cli, Filesystem $files, Configuration $config, Site $site)
    {
        $this->cli = $cli;
        $this->brew = $brew;
        $this->files = $files;
        $this->config = $config;
        $this->site = $site;
    }

    /**
     * Install and configure DnsMasq.
     *
     * @param  array  $links
     * @return void
     */
    function install($links)
    {
        $this->brew->ensureInstalled('dnsmasq');

        // For DnsMasq, we create our own custom configuration file which will be imported
        // in the main DnsMasq file. This allows Valet to make changes to our own files
        // without needing to modify the "primary" DnsMasq configuration files again.
        $this->createCustomConfigFile($links);

        $this->createDomainResolvers($links);

        $this->brew->restartService('dnsmasq');
    }

    /**
     * Install and configure DnsMasq.
     *
     * @param  array  $link
     * @return void
     */
    function remove($link)
    {
        $this->removeDomainResolver($link[4]);
    }

    /**
     * Append the custom DnsMasq configuration file to the main configuration file.
     *
     * @param  array  $links
     * @return void
     */
    function createCustomConfigFile($links)
    {
        $customConfigPath = $this->customConfigPath();

        $this->copyExampleConfig();

        $this->appendCustomConfigImport($customConfigPath);

        $this->files->putAsUser($customConfigPath, $this->makeConfig($links));
    }

    /**
     * Remove all domain resolvers.
     *
     * @param $links
     */
    function removeDomainResolvers($links)
    {
        foreach ($links as $link) {
            $this->removeDomainResolver($link[4]);
        }
    }

    /**
     * Make the Dnsmasq configuration.
     *
     * @param $tld
     * @param $subdomain
     * @return void
     */
    public function updateDomain($tld, $subdomain)
    {
        $this->removeDomainResolvers($this->site->links());

        $this->config->updateKey('tld', $tld);
        $this->config->updateKey('subdomain', $subdomain);

        $this->install($this->site->links()->toArray());
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
     * @param  array $links
     * @return void
     */
    function createDomainResolvers($links)
    {
        $this->files->ensureDirExists($this->resolverPath);

        foreach ($links as $link) {
            $this->createDomainResolver($link[4]);
        }
    }

    /**
     * Create the resolver file to point the configured domain to 127.0.0.1.
     *
     * @param $domain
     * @return void
     */
    function createDomainResolver($domain)
    {
        $this->files->put($this->resolverPath.'/'.$domain, 'nameserver 127.0.0.1'.PHP_EOL);
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

    /**
     * Make the Dnsmasq configuration.
     *
     * @param $links
     * @return string
     */
    function makeConfig($links)
    {
        $result = '';

        foreach ($links as $link) {
            $result .= "address=/.{$link[4]}/127.0.0.1".PHP_EOL;
        }

        if (!is_null($parkDomain = $this->config->get('park_tld'))) {
            $result .= "address=/.{$parkDomain}/127.0.0.1".PHP_EOL;
        }

        return $result . 'listen-address=127.0.0.1'.PHP_EOL;
    }

    /**
     * Remove a domain resolver file.
     *
     * @param $domain
     */
    function removeDomainResolver($domain)
    {
        $this->files->unlink($this->resolverPath.'/'.$domain);
    }

    /**
     * Takeover a domain.
     *
     * @param $tld
     */
    function tldTakeover($tld)
    {
        if(!is_null($oldTld = $this->config->get('park_tld'))) {
            $this->removeDomainResolver($oldTld);
        }

        $this->config->updateKey('park_tld', $tld);

        $this->createDomainResolver($tld);

        $this->createCustomConfigFile($this->site->links());

        $this->brew->restartService('dnsmasq');
    }
}
