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
        'php@7.4',
        'php@7.3',
        'php@7.2',
        'php@7.1',
    ];
    const LATEST_PHP_VERSION = 'php@8.2';

    abstract public function name(): string;

    abstract public function os(): Os;

    // @todo: Figure out which of these don't make sense outside of Brew (e.g. tap?)
    abstract public function hasInstalledPhp(): bool;

    abstract public function installed(string $formula): bool;

    abstract public function determineAliasedVersion(string $formula): string;

    abstract public function hasInstalledNginx(): bool;

    abstract public function nginxServiceName(): string;

    abstract public function installOrFail(string $formula, array $options = [], array $taps = []): void;

    abstract public function restartService(array|string $services): void;

    abstract public function stopService(array|string $services): void;

    abstract public function getLinkedPhpFormula(): string;

    abstract public function linkedPhp(): string;

    abstract public function getPhpExecutablePath(?string $phpVersion = null): string;

    abstract public function createSudoersEntry(): void;

    abstract public function removeSudoersEntry(): void;

    abstract public function link(string $formula, bool $force = false): string;

    abstract public function unlink(string $formula): string;

    abstract public function getAllRunningServices(): Collection;

    abstract public function uninstallAllPhpVersions(): void;

    abstract public function uninstallFormula(string $formula): void;

    /**
     * Determine if php is currently linked.
     */
    public function hasLinkedPhp(): bool
    {
        return $this->files->isLink(BREWAPT_PREFIX.'/bin/php');
    }

    /**
     * Get a list of supported PHP versions.
     */
    public function supportedPhpVersions(): Collection
    {
        return collect(static::SUPPORTED_PHP_VERSIONS);
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

    /**
     * Ensure that the given formula is installed.
     */
    public function ensureInstalled(string $formula, array $options = [], array $taps = []): void
    {
        if (! $this->installed($formula)) {
            $this->installOrFail($formula, $options, $taps);
        }
    }
}
