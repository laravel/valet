<?php

namespace Valet;

use Symfony\Component\Process\Process;

class DnsMasq
{
    var $brew, $cli, $files;

    var $resolverPath = '/etc/resolver';
    var $configPath = '/usr/local/etc/dnsmasq.conf';
    var $exampleConfigPath = '/usr/local/opt/dnsmasq/dnsmasq.conf.example';

    var $domainAddressPattern = 'address=/.%s/127.0.0.1';

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

        $this->restart();
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

        $this->files->putAsUser($customConfigPath, sprintf($this->domainAddressPattern, $domain).PHP_EOL);
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
     * Add new domain.
     *
     * @param  string $domain
     * @return void
     */
    function addDomain($domain)
    {
        $this->createDomainResolver($domain);

        $this->addDomainToCustomConfig($domain);

        $this->restart();
    }

    /**
     * Add domain to custom configuration file.
     *
     * @param  string $domain
     * @return void
     */
    function addDomainToCustomConfig($domain)
    {
        $config = $this->files->get($this->customConfigPath());

        $domainAddress = sprintf($this->domainAddressPattern, $domain);

        if (strpos($config, $domainAddress) !== false) {
            return;
        }

        $this->files->appendAsUser($this->customConfigPath(), $domainAddress.PHP_EOL);
    }

    /**
     * Create the resolver file to point the given domain to 127.0.0.1.
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
     * Rename existing domain.
     *
     * @param  string  $oldDomain
     * @param  string  $newDomain
     * @return void
     */
    function renameDomain($oldDomain, $newDomain)
    {
        $this->renameDomainResolver($oldDomain, $newDomain);

        $this->renameDomainInCustomConfig($oldDomain, $newDomain);

        $this->restart();
    }

    /**
     * Rename domain to custom configuration file.
     *
     * @param  string $oldDomain
     * @param  string $newDomain
     * @return void
     */
    function renameDomainInCustomConfig($oldDomain, $newDomain)
    {
        $customConfigPath = $this->customConfigPath();

        $this->files->putAsUser($customConfigPath, str_replace(
            sprintf($this->domainAddressPattern, $oldDomain),
            sprintf($this->domainAddressPattern, $newDomain),
            $this->files->get($customConfigPath)
        ));
    }

    /**
     * Rename domain resolver file
     *
     * @param  string $oldDomain
     * @param  string $newDomain
     * @return void
     */
    function renameDomainResolver($oldDomain, $newDomain)
    {
        $this->files->ensureDirExists($this->resolverPath);

        $this->files->rename($this->resolverPath.'/'.$oldDomain, $this->resolverPath.'/'.$newDomain);
    }

    /**
     * Delete existing domain.
     *
     * @param  string $domain
     * @return void
     */
    function deleteDomain($domain)
    {
        $this->deleteDomainResolver($domain);

        $this->removeDomainFromCustomConfig($domain);

        $this->restart();
    }

    /**
     * Delete domain resolver file
     *
     * @param  string $domain
     * @return void
     */
    function deleteDomainResolver($domain)
    {
        $this->files->ensureDirExists($this->resolverPath);

        $this->files->unlink($this->resolverPath.'/'.$domain);
    }

    /**
     * Remove domain from custom configuration file.
     *
     * @param  string $domain
     * @return void
     */
    function removeDomainFromCustomConfig($domain)
    {
        $customConfigPath = $this->customConfigPath();

        $this->files->putAsUser($customConfigPath, collect(file($customConfigPath))->reject(function($domainAddress) use ($domain) {
            return strpos($domainAddress, '.' . $domain) !== false;
        })->implode(''));
    }

    /**
     * Flush DNS cache
     *
     * @return void
     */
    function flushDnsCache()
    {
        // Determine current OS X version
        list($majorVersion, $minorVersion, $buildVersion) = explode('.', shell_exec('sw_vers -productVersion'));

        if ($majorVersion == 10 && in_array($minorVersion, [5, 6])) {
            // OS X 10.5 - 10.6
            $this->cli->quietlyAsUser('dscacheutil -flushcache');
        } elseif ($majorVersion == 10 && $minorVersion == 10 && in_array($buildVersion, [0, 1, 2, 3])) {
            // OS X 10.10.0 - 10.10.3
            $this->cli->quietlyAsUser('discoveryutil mdnsflushcache');
        } else {
            // OS X 10.7 - 10.9
            // OS X 10.10.4
            // OS X 10.11
            $this->cli->quietlyAsUser('killall -HUP mDNSResponder');
        }
    }

    /**
     * Restart DnsMasq
     *
     * @return void
     */
    function restart()
    {
        $this->flushDnsCache();

        $this->brew->restartService('dnsmasq');
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
