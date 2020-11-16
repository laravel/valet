<?php

namespace Valet;

use DomainException;

class PhpFpm
{
    var $brew, $cli, $files;

    var $taps = [
        'homebrew/homebrew-core'
    ];

    /**
     * Create a new PHP FPM class instance.
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
     * Install and configure PhpFpm.
     *
     * @return void
     */
    function install()
    {
        if (! $this->brew->hasInstalledPhp()) {
            $this->brew->ensureInstalled('php', [], $this->taps);
        }

        $this->files->ensureDirExists('/usr/local/var/log', user());

        $this->updateConfiguration();

        $this->restart();
    }

    /**
     * Forcefully uninstall all of Valet's supported PHP versions and configurations
     * 
     * @return void
     */
    function uninstall()
    {
        $this->brew->uninstallAllPhpVersions();
        rename(BREW_PREFIX.'/etc/php', BREW_PREFIX.'/etc/php-valet-bak'.time());
        $this->cli->run('rm -rf '.BREW_PREFIX.'/var/log/php-fpm.log');
    }

    /**
     * Update the PHP FPM configuration.
     *
     * @return void
     */
    function updateConfiguration()
    {
        info('Updating PHP configuration...');

        $fpmConfigFile = $this->fpmConfigPath();

        $this->files->ensureDirExists(dirname($fpmConfigFile), user());

        // rename (to disable) old FPM Pool configuration, regardless of whether it's a default config or one customized by an older Valet version
        $oldFile = dirname($fpmConfigFile) . '/www.conf';
        if (file_exists($oldFile)) {
            rename($oldFile, $oldFile . '-backup');
        }

        if (false === strpos($fpmConfigFile, '5.6')) {
            // for PHP 7 we can simply drop in a valet-specific fpm pool config, and not touch the default config
            $contents = $this->files->get(__DIR__.'/../stubs/etc-phpfpm-valet.conf');
            $contents = str_replace(['VALET_USER', 'VALET_HOME_PATH'], [user(), VALET_HOME_PATH], $contents);
        } else {
            // for PHP 5 we must do a direct edit of the fpm pool config to switch it to Valet's needs
            $contents = $this->files->get($fpmConfigFile);
            $contents = preg_replace('/^user = .+$/m', 'user = '.user(), $contents);
            $contents = preg_replace('/^group = .+$/m', 'group = staff', $contents);
            $contents = preg_replace('/^listen = .+$/m', 'listen = '.VALET_HOME_PATH.'/valet.sock', $contents);
            $contents = preg_replace('/^;?listen\.owner = .+$/m', 'listen.owner = '.user(), $contents);
            $contents = preg_replace('/^;?listen\.group = .+$/m', 'listen.group = staff', $contents);
            $contents = preg_replace('/^;?listen\.mode = .+$/m', 'listen.mode = 0777', $contents);
        }
        $this->files->put($fpmConfigFile, $contents);

        $contents = $this->files->get(__DIR__.'/../stubs/php-memory-limits.ini');

        $destFile = dirname($fpmConfigFile);
        $destFile = str_replace('/php-fpm.d', '', $destFile);
        $destFile .= '/conf.d/php-memory-limits.ini';
        $this->files->ensureDirExists(dirname($destFile), user());

        $this->files->putAsUser($destFile, $contents);
    }

    /**
     * Restart the PHP FPM process.
     *
     * @return void
     */
    function restart()
    {
        $this->brew->restartLinkedPhp();
    }

    /**
     * Stop the PHP FPM process.
     *
     * @return void
     */
    function stop()
    {
        call_user_func_array(
            [$this->brew, 'stopService'],
            Brew::SUPPORTED_PHP_VERSIONS
        );
    }

    /**
     * Get the path to the FPM configuration file for the current PHP version.
     *
     * @return string
     */
    function fpmConfigPath()
    {
        $version = $this->brew->linkedPhp();

        $versionNormalized = preg_replace(
            '/php@?(\d)\.?(\d)/',
            '$1.$2',
            $version === 'php' ? Brew::LATEST_PHP_VERSION : $version
        );

        return $versionNormalized === '5.6'
            ? BREW_PREFIX.'/etc/php/5.6/php-fpm.conf'
            : BREW_PREFIX."/etc/php/${versionNormalized}/php-fpm.d/valet-fpm.conf";
    }

    /**
     * Only stop running php services
     */
    function stopRunning()
    {
        $this->brew->stopService(
            $this->brew->getRunningServices()
                ->filter(function ($service) {
                    return substr($service, 0, 3) === 'php';
                })
                ->all()
        );
    }

    /**
     * Use a specific version of php
     *
     * @param $version
     * @return string
     */
    function useVersion($version)
    {
        $version = $this->validateRequestedVersion($version);

        // Install the relevant formula if not already installed
        $this->brew->ensureInstalled($version);

        // Unlink the current php if there is one
        if ($this->brew->hasLinkedPhp()) {
            $currentVersion = $this->brew->getLinkedPhpFormula();
            info(sprintf('Unlinking current version: %s', $currentVersion));
            $this->brew->unlink($currentVersion);
        }

        info(sprintf('Linking new version: %s', $version));
        $this->brew->link($version, true);

        $this->install();

        return $version === 'php' ? $this->brew->determineAliasedVersion($version) : $version;
    }

    /**
     * Validate the requested version to be sure we can support it.
     *
     * @param $version
     * @return string
     */
    function validateRequestedVersion($version)
    {
        // If passed php7.2 or php72 formats, normalize to php@7.2 format:
        $version = preg_replace('/(php)([0-9+])(?:.)?([0-9+])/i', '$1@$2.$3', $version);

        if ($version === 'php') {
            if (strpos($this->brew->determineAliasedVersion($version), '@')) {
                return $version;
            }
        
            if ($this->brew->hasInstalledPhp()) {
                throw new DomainException('Brew is already using PHP '.PHP_VERSION.' as \'php\' in Homebrew. To use another version, please specify. eg: php@7.3');
            }
        }

        if (!$this->brew->supportedPhpVersions()->contains($version)) {
            throw new DomainException(
                sprintf(
                    'Valet doesn\'t support PHP version: %s (try something like \'php@7.3\' instead)',
                    $version
                )
            );
        }

        return $version;
    }
}
