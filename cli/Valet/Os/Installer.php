<?php

namespace Valet\Os;

use Illuminate\Support\Collection;

// @todo: This is installer *and* service manager. May need to be split or renamed.
abstract class Installer
{
    const SUPPORTED_PHP_VERSIONS = [
        'php',
        'php@8.2',
        'php@8.1',
        'php@8.0',
    ];
    const LATEST_PHP_VERSION = 'php@8.2';

    // @todo: Figure out which of these don't make sense outside of Brew (e.g. tap?)
    abstract public function installed(string $formula): bool;
    abstract public function hasInstalledPhp(): bool;
    abstract public function installedPhpFormulae(): Collection;
    abstract public function determineAliasedVersion(string $formula): string;
    abstract public function hasInstalledNginx(): bool;
    abstract public function nginxServiceName(): string;
    abstract public function ensureInstalled(string $formula, array $options = [], array $taps = []): void;
    abstract public function installOrFail(string $formula, array $options = [], array $taps = []): void;
    // Skipping tap(), as that's only used internally
    abstract public function restartService(array|string $services): void;
    abstract public function stopService(array|string $services): void;
    abstract public function hasLinkedPhp(): bool;
    abstract public function getLinkedPhpFormula(): string;
    abstract public function linkedPhp(): string;
    abstract public function getPhpExecutablePath(?string $phpVersion = null): string;
    abstract public function restartLinkedPhp(): void;
    abstract public function createSudoersEntry(): void;
    abstract public function removeSudoersEntry(): void;
    abstract public function link(string $formula, bool $force = false): string;
    abstract public function unlink(string $formula): string;
    abstract public function getAllRunningServices(): Collection;
    abstract public function uninstallAllPhpVersions(): void;
    abstract public function uninstallFormula(string $formula): void;
    abstract public function parsePhpPath(string $resolvedPath): array;

    /**
     * Get a list of supported PHP versions.
     *
     * @return Collection
     */
    public function supportedPhpVersions(): Collection
    {
        return collect(static::SUPPORTED_PHP_VERSIONS);
    }

    /**
     * Check if two PHP versions are equal.
     *
     * @param  string  $versionA
     * @param  string  $versionB
     * @return bool
     */
    public function arePhpVersionsEqual(string $versionA, string $versionB): bool
    {
        $versionANormalized = preg_replace('/[^\d]/', '', $versionA);
        $versionBNormalized = preg_replace('/[^\d]/', '', $versionB);

        return $versionANormalized === $versionBNormalized;
    }
}
