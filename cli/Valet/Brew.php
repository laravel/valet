<?php

namespace Valet;

use DomainException;
use Illuminate\Support\Collection;
use PhpFpm;

class Brew
{
    // This is the array of PHP versions that Valet will attempt to install/configure when requested
    const SUPPORTED_PHP_VERSIONS = [
        'php',
        'php@8.3',
        'php@8.2',
        'php@8.1',
        'php@8.0',
        'php@7.4',
        'php@7.3',
        'php@7.2',
        'php@7.1',
    ];

    // Update this LATEST and the following LIMITED array when PHP versions are released or retired
    // We specify a numbered version here even though Homebrew links its generic 'php' alias to it
    const LATEST_PHP_VERSION = 'php@8.2';

    // These are the PHP versions that should be installed via the shivammathur/php tap because
    // Homebrew officially no longer bottles them or they're marked disabled in their formula
    // Cue: Homebrew reports "php@7.4 has been disabled because it is a versioned formula"
    const LIMITED_PHP_VERSIONS = [
        'php@8.0',
        'php@7.4',
        'php@7.3',
        'php@7.2',
        'php@7.1',
    ];

    const BREW_DISABLE_AUTO_CLEANUP = 'HOMEBREW_NO_INSTALL_CLEANUP=1';

    public function __construct(public CommandLine $cli, public Filesystem $files)
    {
    }

    /**
     * Ensure the formula exists in the current Homebrew configuration.
     */
    public function installed(string $formula): bool
    {
        $result = $this->cli->runAsUser("brew info $formula --json=v2");

        // should be a json response, but if not installed then "Error: No available formula ..."
        if (starts_with($result, 'Error: No')) {
            return false;
        }

        $details = json_decode($result, true);

        if (! empty($details['formulae'])) {
            return ! empty($details['formulae'][0]['installed']);
        }

        if (! empty($details['casks'])) {
            return ! is_null($details['casks'][0]['installed']);
        }

        return false;
    }

    /**
     * Determine if a compatible PHP version is Homebrewed.
     */
    public function hasInstalledPhp(): bool
    {
        $installed = $this->installedPhpFormulae()->first(function ($formula) {
            return $this->supportedPhpVersions()->contains($formula);
        });

        return ! empty($installed);
    }

    /**
     * Get a list of supported PHP versions.
     */
    public function supportedPhpVersions(): Collection
    {
        return collect(static::SUPPORTED_PHP_VERSIONS);
    }

    /**
     * Get a list of disabled/limited PHP versions.
     */
    public function limitedPhpVersions(): Collection
    {
        return collect(static::LIMITED_PHP_VERSIONS);
    }

    /**
     * Get a list of installed PHP formulae.
     */
    public function installedPhpFormulae(): Collection
    {
        return collect(
            explode(PHP_EOL, $this->cli->runAsUser('brew list --formula | grep php'))
        );
    }

    /**
     * Get the aliased formula version from Homebrew.
     */
    public function determineAliasedVersion($formula): string
    {
        $details = json_decode($this->cli->runAsUser("brew info $formula --json"));

        if (! empty($details[0]->aliases[0])) {
            return $details[0]->aliases[0];
        }

        return 'ERROR - NO BREW ALIAS FOUND';
    }

    /**
     * Determine if a compatible nginx version is Homebrewed.
     */
    public function hasInstalledNginx(): bool
    {
        return $this->installed('nginx')
            || $this->installed('nginx-full');
    }

    /**
     * Return name of the nginx service installed via Homebrew.
     */
    public function nginxServiceName(): string
    {
        return $this->installed('nginx-full') ? 'nginx-full' : 'nginx';
    }

    /**
     * Ensure that the given formula is installed.
     */
    public function ensureInstalled(string $formula, array $options = [], array $taps = []): void
    {
        if (! $this->installed($formula)) {
            $this->installOrFail($formula, $options, $taps);
        }
    }

    /**
     * Install the given formula and throw an exception on failure.
     */
    public function installOrFail(string $formula, array $options = [], array $taps = []): void
    {
        info("Installing {$formula}...");

        if (count($taps) > 0) {
            $this->tap($taps);
        }

        output('<info>['.$formula.'] is not installed, installing it now via Brew...</info> 🍻');

        if ($this->limitedPhpVersions()->contains($formula)) {
            $formula = 'shivammathur/php/'.$formula;
            warning('Note: older PHP versions may take 10+ minutes to compile from source. Please wait ...');
        }

        $this->cli->runAsUser(trim(static::BREW_DISABLE_AUTO_CLEANUP.' brew install '.$formula.' '.implode(' ', $options)), function ($exitCode, $errorOutput) use ($formula) {
            output($errorOutput);

            throw new DomainException('Brew was unable to install ['.$formula.'].');
        });
    }

    /**
     * Tap the given formulas.
     */
    public function tap($formulas): void
    {
        $formulas = is_array($formulas) ? $formulas : func_get_args();

        foreach ($formulas as $formula) {
            $this->cli->passthru(static::BREW_DISABLE_AUTO_CLEANUP.' sudo -u "'.user().'" brew tap '.$formula);
        }
    }

    /**
     * Restart the given Homebrew services.
     */
    public function restartService($services): void
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
     */
    public function stopService($services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            if ($this->installed($service)) {
                info("Stopping {$service}...");

                // first we ensure that the service is not incorrectly running as non-root
                $this->cli->quietly('brew services stop '.$service);

                // stop the sudo version
                $this->cli->quietly('sudo brew services stop '.$service);

                // restore folder permissions: for each brew formula, these directories are owned by root:admin
                $directories = [
                    BREW_PREFIX."/Cellar/$service",
                    BREW_PREFIX."/opt/$service",
                    BREW_PREFIX."/var/homebrew/linked/$service",
                ];

                $whoami = get_current_user();

                foreach ($directories as $directory) {
                    $this->cli->quietly("sudo chown -R {$whoami}:admin '$directory'");
                }
            }
        }
    }

    /**
     * Determine if php is currently linked.
     */
    public function hasLinkedPhp(): bool
    {
        return $this->files->isLink(BREW_PREFIX.'/bin/php');
    }

    /**
     * Get the linked php parsed.
     */
    public function getParsedLinkedPhp(): array
    {
        if (! $this->hasLinkedPhp()) {
            throw new DomainException('Homebrew PHP appears not to be linked. Please run [valet use php@X.Y]');
        }

        $resolvedPath = $this->files->readLink(BREW_PREFIX.'/bin/php');

        return $this->parsePhpPath($resolvedPath);
    }

    /**
     * Gets the currently linked formula by identifying the symlink in the hombrew bin directory.
     * Different to ->linkedPhp() in that this will just get the linked directory name,
     * whether that is php, php74 or php@7.4.
     */
    public function getLinkedPhpFormula(): string
    {
        $matches = $this->getParsedLinkedPhp();

        return $matches[1].$matches[2];
    }

    /**
     * Determine which version of PHP is linked in Homebrew.
     */
    public function linkedPhp(): string
    {
        $matches = $this->getParsedLinkedPhp();
        $resolvedPhpVersion = $matches[3] ?: $matches[2];

        return $this->supportedPhpVersions()->first(
            function ($version) use ($resolvedPhpVersion) {
                return $this->arePhpVersionsEqual($resolvedPhpVersion, $version);
            }, function () use ($resolvedPhpVersion) {
                throw new DomainException("Unable to determine linked PHP when parsing '$resolvedPhpVersion'");
            });
    }

    /**
     * Extract PHP executable path from PHP Version.
     *
     * @param  string|null  $phpVersion  For example, "php@8.1"
     */
    public function getPhpExecutablePath(?string $phpVersion = null): string
    {
        if (! $phpVersion) {
            return BREW_PREFIX.'/bin/php';
        }

        $phpVersion = PhpFpm::normalizePhpVersion($phpVersion);

        // Check the default `/opt/homebrew/opt/php@8.1/bin/php` location first
        if ($this->files->exists(BREW_PREFIX."/opt/{$phpVersion}/bin/php")) {
            return BREW_PREFIX."/opt/{$phpVersion}/bin/php";
        }

        // Check the `/opt/homebrew/opt/php71/bin/php` location for older installations
        $phpVersion = str_replace(['@', '.'], '', $phpVersion); // php@8.1 to php81
        if ($this->files->exists(BREW_PREFIX."/opt/{$phpVersion}/bin/php")) {
            return BREW_PREFIX."/opt/{$phpVersion}/bin/php";
        }

        // Check if the default PHP is the version we are looking for
        if ($this->files->isLink(BREW_PREFIX.'/opt/php')) {
            $resolvedPath = $this->files->readLink(BREW_PREFIX.'/opt/php');
            $matches = $this->parsePhpPath($resolvedPath);
            $resolvedPhpVersion = $matches[3] ?: $matches[2];

            if ($this->arePhpVersionsEqual($resolvedPhpVersion, $phpVersion)) {
                return BREW_PREFIX.'/opt/php/bin/php';
            }
        }

        return BREW_PREFIX.'/bin/php';
    }

    /**
     * Restart the linked PHP-FPM Homebrew service.
     */
    public function restartLinkedPhp(): void
    {
        $this->restartService($this->getLinkedPhpFormula());
    }

    /**
     * Create the "sudoers.d" entry for running Brew.
     */
    public function createSudoersEntry(): void
    {
        $this->files->ensureDirExists('/etc/sudoers.d');

        $this->files->put('/etc/sudoers.d/brew', 'Cmnd_Alias BREW = '.BREW_PREFIX.'/bin/brew *
%admin ALL=(root) NOPASSWD:SETENV: BREW'.PHP_EOL);
    }

    /**
     * Remove the "sudoers.d" entry for running Brew.
     */
    public function removeSudoersEntry(): void
    {
        $this->cli->quietly('rm /etc/sudoers.d/brew');
    }

    /**
     * Link passed formula.
     */
    public function link(string $formula, bool $force = false): string
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
     */
    public function unlink(string $formula): string
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
     */
    public function getAllRunningServices(): Collection
    {
        return $this->getRunningServicesAsRoot()
            ->concat($this->getRunningServicesAsUser())
            ->unique();
    }

    /**
     * Get the currently running brew services as root.
     * i.e. /Library/LaunchDaemons (started at boot).
     */
    public function getRunningServicesAsRoot(): Collection
    {
        return $this->getRunningServices();
    }

    /**
     * Get the currently running brew services.
     * i.e. ~/Library/LaunchAgents (started at login).
     */
    public function getRunningServicesAsUser(): Collection
    {
        return $this->getRunningServices(true);
    }

    /**
     * Get the currently running brew services.
     */
    public function getRunningServices(bool $asUser = false): Collection
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
     */
    public function uninstallAllPhpVersions(): string
    {
        $this->supportedPhpVersions()->each(function ($formula) {
            $this->uninstallFormula($formula);
        });

        return 'PHP versions removed.';
    }

    /**
     * Uninstall a Homebrew app by formula name.
     */
    public function uninstallFormula(string $formula): void
    {
        $this->cli->runAsUser(static::BREW_DISABLE_AUTO_CLEANUP.' brew uninstall --force '.$formula);
        $this->cli->run('rm -rf '.BREW_PREFIX.'/Cellar/'.$formula);
    }

    /**
     * Run Homebrew's cleanup commands.
     */
    public function cleanupBrew(): string
    {
        return $this->cli->runAsUser(
            'brew cleanup && brew services cleanup',
            function ($exitCode, $errorOutput) {
                output($errorOutput);
            }
        );
    }

    /**
     * Parse homebrew PHP Path.
     */
    public function parsePhpPath(string $resolvedPath): array
    {
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
     * Check if two PHP versions are equal.
     */
    public function arePhpVersionsEqual(string $versionA, string $versionB): bool
    {
        $versionANormalized = preg_replace('/[^\d]/', '', $versionA);
        $versionBNormalized = preg_replace('/[^\d]/', '', $versionB);

        return $versionANormalized === $versionBNormalized;
    }
}
