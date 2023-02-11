<?php

namespace Valet\Os\Linux;

use DomainException;
use Illuminate\Support\Collection;
use Valet\CommandLine;
use Valet\Filesystem;
use function Valet\info;
use Valet\Os\Installer;
use function Valet\output;

class Apt extends Installer
{
    public $nginxServiceNames = [
        'nginx',
        'nginx-full',
        'nginx-core',
        'nginx-light',
        'nginx-extras',
    ];

    /**
     * Create a new Apt instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     */
    public function __construct(public CommandLine $cli, public Filesystem $files)
    {
    }

    public function name(): string
    {
        return 'Apt';
    }

    public function installed(string $formula): bool
    {
        $output = $this->cli->runAsUser("dpkg -s $formula &> /dev/null");

        return ! str_contains($output, 'is not installed');
    }

    public function hasInstalledPhp(): bool
    {
        return true; // @todo
    }

    /**
     * Determine if a compatible nginx version is installed via Apt.
     */
    public function hasInstalledNginx(): bool
    {
        foreach ($this->nginxServiceNames as $name) {
            if ($this->installed($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return name of the nginx service installed via Apt.
     */
    public function nginxServiceName(): string
    {
        foreach ($this->nginxServiceNames as $name) {
            if ($this->installed($name)) {
                return $name;
            }
        }

        return 'nginx';
    }

    public function determineAliasedVersion(string $formula): string
    {
        return '@todo real thing here';
    }

    public function installOrFail(string $formula, array $options = [], array $taps = []): void
    {
        info("Installing {$formula}...");
        output('<info>['.$formula.'] is not installed; installing it now via Apt...</info>');

        $this->cli->run('apt install -y '.$formula.' '.implode(' ', $options), function ($exitCode, $errorOutput) use ($formula) {
            output($errorOutput);

            throw new DomainException('Apt was unable to install ['.$formula.'].');
        });
    }

    public function restartService(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            if ($this->installed($service)) {
                info("Stopping {$service}...");

                $this->cli->quietly('sudo systemctl restart '.$service);
            }
        }
    }

    public function stopService(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            if ($this->installed($service)) {
                info("Stopping {$service}...");

                $this->cli->quietly('sudo systemctl stop '.$service);
            }
        }
    }

    public function getLinkedPhpFormula(): string
    {
        if (! $this->hasLinkedPhp()) {
            throw new DomainException('Apt PHP appears not to be linked. Please run [valet use php@X.Y]');
        }

        $resolvedPath = $this->files->deepReadLink('/usr/bin/php');

        // @todo: Probably make this a regex
        $split = explode('/', $resolvedPath);
        return $split[3];
    }

    public function linkedPhp(): string
    {
        $resolvedPhpVersion = $this->getLinkedPhpFormula();

        return $this->supportedPhpVersions()->first(
            function ($version) use ($resolvedPhpVersion) {
                return $this->arePhpVersionsEqual($resolvedPhpVersion, $version);
            },
            function () use ($resolvedPhpVersion) {
                throw new DomainException("Unable to determine linked PHP when parsing '$resolvedPhpVersion'");
            }
        );
    }

    public function getPhpExecutablePath(?string $phpVersion = null): string
    {
        if (! $phpVersion) {
            return '/usr/bin/php';
        }

        // @todo: Make this actually work
        return '/usr/bin/php';
    }

    public function createSudoersEntry(): void
    {
        // @todo
    }

    public function removeSudoersEntry(): void
    {
        // @todo
    }

    public function link(string $formula, bool $force = false): string
    {
        // sudo update-alternatives --set php /usr/bin/php5.6
        return 'yay it worked @todo';
    }

    public function unlink(string $formula): string
    {
        return 'yay it worked @todo';
    }

    public function getAllRunningServices(): Collection
    {
        // @todo
        return collect([]);
    }

    public function uninstallAllPhpVersions(): void
    {
        // @todo
    }

    public function uninstallFormula(string $formula): void
    {
        // @todo sudo apt remove $formula
    }

    /**
     * Get a list of supported PHP versions.
     */
    public function supportedPhpVersions(): Collection
    {
        return collect(static::SUPPORTED_PHP_VERSIONS)->map(function ($version) {
            return str_replace('@', '', $version);
        });
    }
}
