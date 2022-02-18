<?php

namespace Valet;

use DomainException;

class PhpFpm
{
    public $brew;
    public $cli;
    public $files;
    public $config;
    public $site;
    public $nginx;

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
     * @param  Configuration  $config
     * @param  Site  $site
     * @param  Nginx  $nginx
     * @return void
     */
    public function __construct(Brew $brew, CommandLine $cli, Filesystem $files, Configuration $config, Site $site, Nginx $nginx)
    {
        $this->cli = $cli;
        $this->brew = $brew;
        $this->files = $files;
        $this->config = $config;
        $this->site = $site;
        $this->nginx = $nginx;
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
     * @param  string|null  $phpVersion
     * @return void
     */
    public function updateConfiguration($phpVersion = null)
    {
        info(sprintf('Updating PHP configuration%s...', $phpVersion ? ' for '.$phpVersion : ''));

        $fpmConfigFile = $this->fpmConfigPath($phpVersion);

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

        if ($phpVersion) {
            $contents = str_replace('valet.sock', $this->fpmSockName($phpVersion), $contents);
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
     * @param  string|null  $phpVersion
     * @return void
     */
    public function restart($phpVersion = null)
    {
        $this->brew->restartService($phpVersion ?: $this->utilizedPhpVersions());
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
     * @param  string|null  $phpVersion
     * @return string
     */
    public function fpmConfigPath($phpVersion = null)
    {
        if (! $phpVersion) {
            $phpVersion = $this->brew->linkedPhp();
        }

        $versionNormalized = $this->normalizePhpVersion($phpVersion === 'php' ? Brew::LATEST_PHP_VERSION : $phpVersion);
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
            $this->brew->getAllRunningServices()
                ->filter(function ($service) {
                    return substr($service, 0, 3) === 'php';
                })
                ->all()
        );
    }

    /**
     * Stop PHP, if a specific version isn't being used globally or by any sites.
     *
     * @param  string|null  $phpVersion
     * @return void
     */
    public function stopIfUnused($phpVersion)
    {
        if (! $phpVersion) {
            return;
        }

        if (strpos($phpVersion, 'php') === false) {
            $phpVersion = 'php'.$phpVersion;
        }

        $phpVersion = $this->normalizePhpVersion($phpVersion);

        if (! in_array($phpVersion, $this->utilizedPhpVersions())) {
            $this->brew->stopService($phpVersion);
        }
    }

    /**
     * Use a specific version of php.
     *
     * @param  string  $version
     * @param  bool  $force
     * @param  string|null  $directory
     * @return string|void
     */
    public function useVersion($version, $force = false, $directory = null)
    {
        if ($directory) {
            $site = $this->site->getSiteUrl($directory);

            if (! $site) {
                throw new DomainException(
                    sprintf(
                        "The [%s] site could not be found in Valet's site list.",
                        $directory
                    )
                );
            }

            if ($version == 'default') { // Remove isolation for this site
                $oldCustomPhpVersion = $this->site->customPhpVersion($site); // Example output: "74"
                $this->site->removeIsolation($site);
                $this->stopIfUnused($oldCustomPhpVersion);
                $this->nginx->restart();
                info(sprintf('The site [%s] is now using the default PHP version.', $site));

                return;
            }
        }

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

        if ($directory) {
            $oldCustomPhpVersion = $this->site->customPhpVersion($site); // Example output: "74"
            $this->cli->quietly('sudo rm '.VALET_HOME_PATH.'/'.$this->fpmSockName($version));
            $this->updateConfiguration($version);
            $this->site->installSiteConfig($site, $this->fpmSockName($version), $version);

            $this->stopIfUnused($oldCustomPhpVersion);
            $this->restart($version);
            $this->nginx->restart();
            info(sprintf('The site [%s] is now using %s.', $site, $version));

            return;
        }

        // Unlink the current global PHP if there is one installed
        if ($this->brew->hasLinkedPhp()) {
            $linkedPhp = $this->brew->linkedPhp();

            // Update the old FPM to keep running, using a custom sock file, so existing isolated sites aren't broken
            $this->updateConfiguration($linkedPhp);

            // Update existing custom Nginx config files; if they're using the old or new PHP version,
            // update them to the new correct sock file location
            $this->updateConfigurationForGlobalUpdate($version, $linkedPhp);

            $currentVersion = $this->brew->getLinkedPhpFormula();
            info(sprintf('Unlinking current version: %s', $currentVersion));
            $this->brew->unlink($currentVersion);
        }

        info(sprintf('Linking new version: %s', $version));
        $this->brew->link($version, true);

        $this->stopRunning();

        // remove any orphaned valet.sock files that PHP didn't clean up due to version conflicts
        $this->files->unlink(VALET_HOME_PATH.'/valet.sock');
        $this->cli->quietly('sudo rm '.VALET_HOME_PATH.'/valet*.sock');

        // ensure configuration is correct and start the linked version
        $this->install();

        $newVersion = $version === 'php' ? $this->brew->determineAliasedVersion($version) : $version;

        $this->nginx->restart();

        info(sprintf('Valet is now using %s.', $newVersion).PHP_EOL);
        info('Note that you might need to run <comment>composer global update</comment> if your PHP version change affects the dependencies of global packages required by Composer.');

        return $newVersion;
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
     * Get FPM sock file name for a given PHP version.
     *
     * @param  string|null  $phpVersion
     * @return string
     */
    public function fpmSockName($phpVersion = null)
    {
        $versionInteger = preg_replace('~[^\d]~', '', $phpVersion);

        return "valet{$versionInteger}.sock";
    }

    /**
     * Update all existing Nginx files when running a global PHP version update.
     * If a given file is pointing to `valet.sock`, it's targeting the old global PHP version;
     * update it to point to the new custom sock file for that version.
     * If a given file is pointing the custom sock file for the new global version, that new
     * version will now be hosted at `valet.sock`, so update the config file to point to that instead.
     *
     * @param  string  $newPhpVersion
     * @param  string  $oldPhpVersion
     * @return void
     */
    public function updateConfigurationForGlobalUpdate($newPhpVersion, $oldPhpVersion)
    {
        collect($this->files->scandir(VALET_HOME_PATH.'/Nginx'))
            ->reject(function ($file) {
                return starts_with($file, '.');
            })
            ->each(function ($file) use ($newPhpVersion, $oldPhpVersion) {
                $content = $this->files->get(VALET_HOME_PATH.'/Nginx/'.$file);

                if (! starts_with($content, '# Valet isolated PHP version')) {
                    return;
                }

                if (strpos($content, $this->fpmSockName($newPhpVersion)) !== false) {
                    info(sprintf('Updating site %s to keep using version: %s', $file, $newPhpVersion));
                    $this->files->put(VALET_HOME_PATH.'/Nginx/'.$file, str_replace($this->fpmSockName($newPhpVersion), 'valet.sock', $content));
                } elseif (strpos($content, 'valet.sock') !== false) {
                    info(sprintf('Updating site %s to keep using version: %s', $file, $oldPhpVersion));
                    $this->files->put(VALET_HOME_PATH.'/Nginx/'.$file, str_replace('valet.sock', $this->fpmSockName($oldPhpVersion), $content));
                }
            });
    }

    /**
     * Get a list including the global PHP version and allPHP versions currently serving "isolated sites" (sites with
     * custom Nginx configs pointing them to a specific PHP version).
     *
     * @return array
     */
    public function utilizedPhpVersions()
    {
        $fpmSockFiles = $this->brew->supportedPhpVersions()->map(function ($version) {
            return $this->fpmSockName($this->normalizePhpVersion($version));
        })->unique();

        return collect($this->files->scandir(VALET_HOME_PATH.'/Nginx'))
            ->reject(function ($file) {
                return starts_with($file, '.');
            })
            ->map(function ($file) use ($fpmSockFiles) {
                $content = $this->files->get(VALET_HOME_PATH.'/Nginx/'.$file);

                // Get the normalized PHP version for this config file, if it's defined
                foreach ($fpmSockFiles as $sock) {
                    if (strpos($content, $sock) !== false) {
                        // Extract the PHP version number from a custom .sock path;
                        // for example, "valet74.sock" will output "php74"
                        $phpVersion = 'php'.str_replace(['valet', '.sock'], '', $sock);

                        return $this->normalizePhpVersion($phpVersion); // Example output php@7.4
                    }
                }
            })->merge([$this->brew->getLinkedPhpFormula()])->filter()->unique()->values()->toArray();
    }
}
