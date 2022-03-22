<?php

namespace Valet;

use DomainException;

class Brew
{
    const SUPPORTED_PHP_VERSIONS = [
        'php',
        'php@8.1',
        'php@8.0',
        'php@7.4',
        'php@7.3',
        'php@7.2',
        'php@7.1',
        'php@7.0',
        'php73',
        'php72',
        'php71',
        'php70',
    ];

    const LATEST_PHP_VERSION = 'php@8.1';

    public $cli;
    public $files;

    /**
     * Create a new Brew instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    public function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Ensure the formula exists in the current Homebrew configuration.
     *
     * @param  string  $formula
     * @return bool
     */
    public function installed($formula)
    {
        $result = $this->cli->runAsUser("brew info $formula --json");

        // should be a json response, but if not installed then "Error: No available formula ..."
        if (starts_with($result, 'Error: No')) {
            return false;
        }

        $details = json_decode($result);

        return ! empty($details[0]->installed);
    }

    /**
     * Determine if a compatible PHP version is Homebrewed.
     *
     * @return bool
     */
    public function hasInstalledPhp()
    {
        $installed = $this->installedPhpFormulae()->first(function ($formula) {
            return $this->supportedPhpVersions()->contains($formula);
        });

        return ! empty($installed);
    }

    /**
     * Get a list of supported PHP versions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function supportedPhpVersions()
    {
        return collect(static::SUPPORTED_PHP_VERSIONS);
    }

    public function installedPhpFormulae()
    {
        return collect(
            explode(PHP_EOL, $this->cli->runAsUser('brew list --formula | grep php'))
        );
    }

    /**
     * Get the aliased formula version from Homebrew.
     */
    public function determineAliasedVersion($formula)
    {
        $details = json_decode($this->cli->runAsUser("brew info $formula --json"));

        if (! empty($details[0]->aliases[0])) {
            return $details[0]->aliases[0];
        }

        return 'ERROR - NO BREW ALIAS FOUND';
    }

    /**
     * Determine if a compatible nginx version is Homebrewed.
     *
     * @return bool
     */
    public function hasInstalledNginx()
    {
        return $this->installed('nginx')
            || $this->installed('nginx-full');
    }

    /**
     * Return name of the nginx service installed via Homebrew.
     *
     * @return string
     */
    public function nginxServiceName()
    {
        return $this->installed('nginx-full') ? 'nginx-full' : 'nginx';
    }

    /**
     * Ensure that the given formula is installed.
     *
     * @param  string  $formula
     * @param  array  $options
     * @param  array  $taps
     * @return void
     */
    public function ensureInstalled($formula, $options = [], $taps = [])
    {
        if (! $this->installed($formula)) {
            $this->installOrFail($formula, $options, $taps);
        }
    }

    /**
     * Install the given formula and throw an exception on failure.
     *
     * @param  string  $formula
     * @param  array  $options
     * @param  array  $taps
     * @return void
     */
    public function installOrFail($formula, $options = [], $taps = [])
    {
        info("Installing {$formula}...");

        if (count($taps) > 0) {
            $this->tap($taps);
        }

        output('<info>['.$formula.'] is not installed, installing it now via Brew...</info> 🍻');
        if ($formula !== 'php' && starts_with($formula, 'php') && preg_replace('/[^\d]/', '', $formula) < '73') {
            warning('Note: older PHP versions may take 10+ minutes to compile from source. Please wait ...');
        }

        $this->cli->runAsUser(trim('brew install '.$formula.' '.implode(' ', $options)), function ($exitCode, $errorOutput) use ($formula) {
            output($errorOutput);

            throw new DomainException('Brew was unable to install ['.$formula.'].');
        });
    }

    /**
     * Tap the given formulas.
     *
     * @param  dynamic[string]  $formula
     * @return void
     */
    public function tap($formulas)
    {
        $formulas = is_array($formulas) ? $formulas : func_get_args();

        foreach ($formulas as $formula) {
            $this->cli->passthru('sudo -u "'.user().'" brew tap '.$formula);
        }
    }

    /**
     * Restart the given Homebrew services.
     *
     * @param
     */
    public function restartService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            if ($this->installed($service)) {
                info("Restarting {$service}...");

                // first we ensure that the service is not incorrectly running as non-root
                $this->cli->quietly('brew services stop '.$service);
                // stop the actual/correct sudo version
                $this->cli->quietly('sudo brew services stop '.$service);
                // start correctly as root
                $this->cli->quietly('sudo brew services start '.$service);
            }
        }
    }

    /**
     * Stop the given Homebrew services.
     *
     * @param
     */
    public function stopService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            if ($this->installed($service)) {
                info("Stopping {$service}...");

                // first we ensure that the service is not incorrectly running as non-root
                $this->cli->quietly('brew services stop '.$service);

                // stop the sudo version
                $this->cli->quietly('sudo brew services stop '.$service);
            }
        }
    }

    /**
     * Determine if php is currently linked.
     *
     * @return bool
     */
    public function hasLinkedPhp()
    {
        return $this->files->isLink(BREW_PREFIX.'/bin/php');
    }

    /**
     * Get the linked php parsed.
     *
     * @return mixed
     */
    public function getParsedLinkedPhp()
    {
        if (! $this->hasLinkedPhp()) {
            throw new DomainException('Homebrew PHP appears not to be linked. Please run [valet use php@X.Y]');
        }

        $resolvedPath = $this->files->readLink(BREW_PREFIX.'/bin/php');

        /**
         * Typical homebrew path resolutions are like:
         * "../Cellar/php@7.4/7.4.13/bin/php"
         * or older styles:
         * "../Cellar/php/7.4.9_2/bin/php
         * "../Cellar/php55/bin/php.
         */
        preg_match('~\w{3,}/(php)(@?\d\.?\d)?/(\d\.\d)?([_\d\.]*)?/?\w{3,}~', $resolvedPath, $matches);

        return $matches;
    }

    /**
     * Gets the currently linked formula by identifying the symlink in the hombrew bin directory.
     * Different to ->linkedPhp() in that this will just get the linked directory name,
     * whether that is php, php74 or php@7.4.
     *
     * @return string
     */
    public function getLinkedPhpFormula()
    {
        $matches = $this->getParsedLinkedPhp();

        return $matches[1].$matches[2];
    }

    /**
     * Determine which version of PHP is linked in Homebrew.
     *
     * @return string
     */
    public function linkedPhp()
    {
        $matches = $this->getParsedLinkedPhp();
        $resolvedPhpVersion = $matches[3] ?: $matches[2];

        return $this->supportedPhpVersions()->first(
            function ($version) use ($resolvedPhpVersion) {
                $resolvedVersionNormalized = preg_replace('/[^\d]/', '', $resolvedPhpVersion);
                $versionNormalized = preg_replace('/[^\d]/', '', $version);

                return $resolvedVersionNormalized === $versionNormalized;
            }, function () use ($resolvedPhpVersion) {
                throw new DomainException("Unable to determine linked PHP when parsing '$resolvedPhpVersion'");
            });
    }

    /**
     * Get PHP executable path for a given PHP Version.
     *
     * @param  string|null  $phpVersion
     * @param  bool  $skipCache
     * @return string
     */
    public function whichPhp($phpVersion = null, $skipCache = false)
    {
        if (! $phpVersion) {
            return BREW_PREFIX.'/bin/php';
        }

        $versionInteger = preg_replace('~[^\d]~', '', $phpVersion);
        $symlinkedValetPhpPath = BREW_PREFIX."/bin/valetphp{$versionInteger}";

        // If the symlinked Valet PHP path exists, then we can use that
        if (! $skipCache && $this->files->isLink($symlinkedValetPhpPath)) {
            $phpExecutablePath = $this->files->readLink($symlinkedValetPhpPath);

            // Still make sure that the version of the executable exists
            if ($this->files->exists($phpExecutablePath)) {
                return $phpExecutablePath;
            }
        }

        // If the symlinked Valet PHP path doesn't exist, then we need to look for the correct executable path
        // Create a symlink to the Valet PHP version, so next time Valet won't have to look for the executable path
        if ($phpExecutablePath = $this->getPhpExecutablePath($phpVersion)) {
            $this->files->symlinkAsUser($phpExecutablePath, $symlinkedValetPhpPath);
        }

        return $phpExecutablePath ?: BREW_PREFIX.'/bin/php';
    }

    /**
     * Extract PHP executable path from PHP Version.
     *
     * @param  string  $phpVersion
     * @return string
     */
    public function getPhpExecutablePath($phpVersion)
    {
        $phpExecutablePath = null;

        $cellar = $this->cli->runAsUser("brew --cellar $phpVersion"); // Example output: `/opt/homebrew/Cellar/php@8.0`
        $details = json_decode($this->cli->runAsUser("brew info --json $phpVersion"), true);
        $phpDirectory = data_get($details, '0.linked_keg');

        if (is_null($phpDirectory) && $installed = data_get($details, '0.installed')) {
            $phpDirectory = data_get(collect($installed)->where('installed_as_dependency', false)->last(), 'version');

            if (is_null($phpDirectory)) {
                $phpDirectory = data_get(collect($installed)->last(), 'version');
            }
        }

        if (isset($phpDirectory) && $this->files->exists(trim($cellar).'/'.$phpDirectory.'/bin/php')) {
            $phpExecutablePath = trim($cellar).'/'.$phpDirectory.'/bin/php';
        }

        // When PHP Version is installed directly though shivammathur/homebrew-php, it usually stores the binaries in this directory
        if (is_null($phpExecutablePath) && $this->files->exists(BREW_PREFIX."/opt/{$phpVersion}/bin/php")) {
            $phpExecutablePath = BREW_PREFIX."/opt/{$phpVersion}/bin/php";
        }

        return $phpExecutablePath;
    }

    /**
     * Restart the linked PHP-FPM Homebrew service.
     *
     * @return void
     */
    public function restartLinkedPhp()
    {
        $this->restartService($this->getLinkedPhpFormula());
    }

    /**
     * Create the "sudoers.d" entry for running Brew.
     *
     * @return void
     */
    public function createSudoersEntry()
    {
        $this->files->ensureDirExists('/etc/sudoers.d');

        $this->files->put('/etc/sudoers.d/brew', 'Cmnd_Alias BREW = '.BREW_PREFIX.'/bin/brew *
%admin ALL=(root) NOPASSWD:SETENV: BREW'.PHP_EOL);
    }

    /**
     * Remove the "sudoers.d" entry for running Brew.
     *
     * @return void
     */
    public function removeSudoersEntry()
    {
        $this->cli->quietly('rm /etc/sudoers.d/brew');
    }

    /**
     * Link passed formula.
     *
     * @param $formula
     * @param  bool  $force
     * @return string
     */
    public function link($formula, $force = false)
    {
        return $this->cli->runAsUser(
            sprintf('brew link %s%s', $formula, $force ? ' --force' : ''),
            function ($exitCode, $errorOutput) use ($formula) {
                output($errorOutput);

                throw new DomainException('Brew was unable to link ['.$formula.'].');
            }
        );
    }

    /**
     * Unlink passed formula.
     *
     * @param $formula
     * @return string
     */
    public function unlink($formula)
    {
        return $this->cli->runAsUser(
            sprintf('brew unlink %s', $formula),
            function ($exitCode, $errorOutput) use ($formula) {
                output($errorOutput);

                throw new DomainException('Brew was unable to unlink ['.$formula.'].');
            }
        );
    }

    /**
     * Get all the currently running brew services.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllRunningServices()
    {
        return $this->getRunningServicesAsRoot()
            ->concat($this->getRunningServicesAsUser())
            ->unique();
    }

    /**
     * Get the currently running brew services as root.
     * i.e. /Library/LaunchDaemons (started at boot).
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRunningServicesAsRoot()
    {
        return $this->getRunningServices();
    }

    /**
     * Get the currently running brew services.
     * i.e. ~/Library/LaunchAgents (started at login).
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRunningServicesAsUser()
    {
        return $this->getRunningServices(true);
    }

    /**
     * Get the currently running brew services.
     *
     * @param  bool  $asUser
     * @return \Illuminate\Support\Collection
     */
    public function getRunningServices($asUser = false)
    {
        $command = 'brew services list | grep started | awk \'{ print $1; }\'';
        $onError = function ($exitCode, $errorOutput) {
            output($errorOutput);

            throw new DomainException('Brew was unable to check which services are running.');
        };

        return collect(array_filter(explode(PHP_EOL, $asUser
            ? $this->cli->runAsUser($command, $onError)
            : $this->cli->run('sudo '.$command, $onError)
        )));
    }

    /**
     * Tell Homebrew to forcefully remove all PHP versions that Valet supports.
     *
     * @return string
     */
    public function uninstallAllPhpVersions()
    {
        $this->supportedPhpVersions()->each(function ($formula) {
            $this->uninstallFormula($formula);
        });

        return 'PHP versions removed.';
    }

    /**
     * Uninstall a Homebrew app by formula name.
     *
     * @param  string  $formula
     * @return void
     */
    public function uninstallFormula($formula)
    {
        $this->cli->runAsUser('brew uninstall --force '.$formula);
        $this->cli->run('rm -rf '.BREW_PREFIX.'/Cellar/'.$formula);
    }

    /**
     * Run Homebrew's cleanup commands.
     *
     * @return string
     */
    public function cleanupBrew()
    {
        return $this->cli->runAsUser(
            'brew cleanup && brew services cleanup',
            function ($exitCode, $errorOutput) {
                output($errorOutput);
            }
        );
    }
}
