<?php

namespace Valet\Os\Linux;

use Illuminate\Support\Collection;
use Valet\CommandLine;
use Valet\Filesystem;
use Valet\Os\Installer;

class Apt extends Installer
{
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
        return true; // @todo
    }

    public function hasInstalledPhp(): bool
    {
        return true; // @todo
    }

    public function determineAliasedVersion(string $formula): string
    {
        return '@todo real thing here';
    }

    public function hasInstalledNginx(): bool
    {
        return true; // @todo
    }

    public function nginxServiceName(): string
    {
        return 'the best service name @todo';
    }

    public function ensureInstalled(string $formula, array $options = [], array $taps = []): void
    {
        // @todo
    }

    public function installOrFail(string $formula, array $options = [], array $taps = []): void
    {
        // @todo
    }

    public function restartService(array|string $services): void
    {
        // @todo
    }

    public function stopService(array|string $services): void
    {
        // @todo
    }

    public function hasLinkedPhp(): bool
    {
        return true; // @todo
    }

    public function getLinkedPhpFormula(): string
    {
        return 'php @todo';
    }

    public function linkedPhp(): string
    {
        return 'php @todo';
    }

    public function getPhpExecutablePath(?string $phpVersion = null): string
    {
        return '/usr/bin/php'; // @todo
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
        return 'yay it worked @todo';
    }

    public function unlink(string $formula): string
    {
        return 'yay it worked @todo';
    }

    public function getAllRunningServices(): Collection
    {
        return collect([]);
    }

    public function uninstallAllPhpVersions(): void
    {
        // @todo
    }

    public function uninstallFormula(string $formula): void
    {
        // @todo
    }
}
