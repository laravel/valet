<?php

namespace Valet;

use DomainException;

class PhpFpm
{
    public $brew;
    public $cli;
    public $files;

    public $taps = [
        'homebrew/homebrew-core',
        'shivammathur/php',
    ];

    /**
     * Create a new PHP FPM class instance.
     *
     * @param  Brew  $brew
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    public function __construct(Brew $brew, CommandLine $cli, Filesystem $files)
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
    public function install()
    {
        if (! $this->brew->hasInstalledPhp()) {
            $this->brew->ensureInstalled('php', [], $this->taps);
        }

        $this->files->ensureDirExists(VALET_HOME_PATH.'/Log', user());

        $this->updateConfiguration();

        $this->restart();
    }

    /**
     * Forcefully uninstall all of Valet's supported PHP versions and configurations.
     *
     * @return void
     */
    public function uninstall()
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
    public function updateConfiguration()
    {
        info('Updating PHP configuration...');

        $fpmConfigFile = $this->fpmConfigPath();

        $this->files->ensureDirExists(dirname($fpmConfigFile), user());

        // rename (to disable) old FPM Pool configuration, regardless of whether it's a default config or one customized by an older Valet version
        $oldFile = dirname($fpmConfigFile).'/www.conf';
        if (file_exists($oldFile)) {
            rename($oldFile, $oldFile.'-backup');
        }

        if (false === strpos($fpmConfigFile, '5.6')) {
            // since PHP 7 we can simply drop in a valet-specific fpm pool config, and not touch the default config
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

        $contents = $this->files->get(__DIR__.'/../stubs/etc-phpfpm-error_log.ini');
        $contents = str_replace(['VALET_USER', 'VALET_HOME_PATH'], [user(), VALET_HOME_PATH], $contents);
        $destFile = dirname($fpmConfigFile);
        $destFile = str_replace('/php-fpm.d', '', $destFile);
        $destFile .= '/conf.d/error_log.ini';
        $this->files->ensureDirExists(dirname($destFile), user());
        $this->files->putAsUser($destFile, $contents);
        $this->files->ensureDirExists(VALET_HOME_PATH.'/Log', user());
        $this->files->touch(VALET_HOME_PATH.'/Log/php-fpm.log', user());
    }

    /**
     * Restart the PHP FPM process.
     *
     * @return void
     */
    public function restart()
    {
        $this->cleanSockFiles();
        $this->brew->restartLinkedPhp();
    }

    /**
     * Stop the PHP FPM process.
     *
     * @return void
     */
    public function stop()
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
    public function fpmConfigPath()
    {
        $version = $this->brew->linkedPhp();

        $versionNormalized = $this->normalizePhpVersion($version === 'php' ? Brew::LATEST_PHP_VERSION : $version);
        $versionNormalized = preg_replace('~[^\d\.]~', '', $versionNormalized);

        return $versionNormalized === '5.6'
            ? BREW_PREFIX.'/etc/php/5.6/php-fpm.conf'
            : BREW_PREFIX."/etc/php/${versionNormalized}/php-fpm.d/valet-fpm.conf";
    }

    /**
     * Only stop running php services.
     */
    public function stopRunning()
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
     * Use a specific version of php.
     *
     * @param $version
     * @param $force
     * @return string
     */
    public function useVersion($version, $force = false)
    {
        $version = $this->validateRequestedVersion($version);

        try {
            if ($this->brew->linkedPhp() === $version && ! $force) {
                output(sprintf('<info>Valet is already using version: <comment>%s</comment>.</info> To re-link and re-configure use the --force parameter.'.PHP_EOL,
                    $version));
                exit();
            }
        } catch (DomainException $e) { /* ignore thrown exception when no linked php is found */
        }

        if (! $this->brew->installed($version)) {
            // Install the relevant formula if not already installed
            $this->brew->ensureInstalled($version, [], $this->taps);
        }

        // Unlink the current php if there is one
        if ($this->brew->hasLinkedPhp()) {
            $currentVersion = $this->brew->getLinkedPhpFormula();
            info(sprintf('Unlinking current version: %s', $currentVersion));
            $this->brew->unlink($currentVersion);
        }

        info(sprintf('Linking new version: %s', $version));
        $this->brew->link($version, true);

        $this->stopRunning();
        $this->cleanSockFiles();

        // ensure configuration is correct and start the linked version
        $this->install();

        return $version === 'php' ? $this->brew->determineAliasedVersion($version) : $version;
    }

    /**
     * If passed php7.4 or php74 formats, normalize to php@7.4 format.
     */
    public function normalizePhpVersion($version)
    {
        return preg_replace('/(php)([0-9+])(?:.)?([0-9+])/i', '$1@$2.$3', $version);
    }

    /**
     * Validate the requested version to be sure we can support it.
     *
     * @param $version
     * @return string
     */
    public function validateRequestedVersion($version)
    {
        $version = $this->normalizePhpVersion($version);

        if (! $this->brew->supportedPhpVersions()->contains($version)) {
            throw new DomainException(
                sprintf(
                    'Valet doesn\'t support PHP version: %s (try something like \'php@7.3\' instead)',
                    $version
                )
            );
        }

        if (strpos($aliasedVersion = $this->brew->determineAliasedVersion($version), '@')) {
            return $aliasedVersion;
        }

        if ($version === 'php') {
            if (strpos($aliasedVersion = $this->brew->determineAliasedVersion($version), '@')) {
                return $aliasedVersion;
            }

            if ($this->brew->hasInstalledPhp()) {
                throw new DomainException('Brew is already using PHP '.PHP_VERSION.' as \'php\' in Homebrew. To use another version, please specify. eg: php@7.3');
            }
        }

        return $version;
    }

    /**
     * Delete all orphaned valet.sock files.
     *
     * @return void
     */
    public function cleanSockFiles()
    {
        $this->files->unlink(VALET_HOME_PATH.'/valet.sock');
        $this->cli->quietly('sudo rm '.VALET_HOME_PATH.'/valet.sock');
    }
}
