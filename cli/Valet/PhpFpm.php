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
     * Install and configure DnsMasq.
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
     * Update the PHP FPM configuration.
     *
     * @return void
     */
    function updateConfiguration()
    {
        info('Updating PHP configuration...');

        $contents = $this->files->get($this->fpmConfigPath());

        $contents = preg_replace('/^user = .+$/m', 'user = '.user(), $contents);
        $contents = preg_replace('/^group = .+$/m', 'group = staff', $contents);
        $contents = preg_replace('/^listen = .+$/m', 'listen = '.VALET_HOME_PATH.'/valet.sock', $contents);
        $contents = preg_replace('/^;?listen\.owner = .+$/m', 'listen.owner = '.user(), $contents);
        $contents = preg_replace('/^;?listen\.group = .+$/m', 'listen.group = staff', $contents);
        $contents = preg_replace('/^;?listen\.mode = .+$/m', 'listen.mode = 0777', $contents);

        $this->files->put($this->fpmConfigPath(), $contents);


        $contents = $this->files->get(__DIR__.'/../stubs/php-memory-limits.ini');

        $destFile = dirname($this->fpmConfigPath());
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
            ? '/usr/local/etc/php/5.6/php-fpm.conf'
            : "/usr/local/etc/php/${versionNormalized}/php-fpm.d/www.conf";
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
        // If passed php7.2 or php72 formats, convert to php@7.2 format:
        $version = preg_replace('/(php)([0-9+])(?:.)?([0-9+])/i', '$1@$2.$3', $version);

        if (!$this->brew->supportedPhpVersions()->contains($version)) {
            throw new DomainException(
                sprintf('Valet doesnn\'t support PHP version: %s', $version)
            );
        }

        // Install the relevant formula if not already installed
        $this->brew->ensureInstalled($version);

        // Unlink the current php if there is one
        if ($this->brew->hasLinkedPhp()) {
            $currentVersion = $this->brew->linkedPhp();
            info(sprintf('Unlinking current version: %s', $currentVersion));
            $this->brew->unlink($currentVersion);
        }

        info(sprintf('Linking new version: %s', $version));
        $this->brew->link($version, true);

        $this->install();

        return $version;
    }
}
