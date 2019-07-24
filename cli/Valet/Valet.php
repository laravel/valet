<?php

namespace Valet;

use DomainException;
use Illuminate\Container\Container;
use Valet\Contracts\PackageManager;
use Valet\Contracts\ServiceManager;
use Valet\PackageManagers\Apt;
use Valet\PackageManagers\Dnf;
use Valet\PackageManagers\PackageKit;
use Valet\PackageManagers\Pacman;
use Valet\PackageManagers\Yum;
use Valet\PackageManagers\Eopkg;
use Valet\ServiceManagers\LinuxService;
use Valet\ServiceManagers\Systemd;

class Valet
{
    public $cli;
    public $files;

    public $valetBin = '/usr/local/bin/valet';
    public $sudoers  = '/etc/sudoers.d/valet';
    public $github   = 'https://api.github.com/repos/cpriego/valet-linux/releases/latest';

    /**
     * Create a new Valet instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     */
    public function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Symlink the Valet Bash script into the user's local bin.
     *
     * @return void
     */
    public function symlinkToUsersBin()
    {
        $this->cli->run('ln -snf '.realpath(__DIR__.'/../../valet').' '.$this->valetBin);
    }

    /**
     * Unlink the Valet Bash script from the user's local bin
     * and the sudoers.d entry
     *
     * @return void
     */
    public function uninstall()
    {
        $this->files->unlink($this->valetBin);
        $this->files->unlink($this->sudoers);
    }

    /**
     * Get the paths to all of the Valet extensions.
     *
     * @return array
     */
    public function extensions()
    {
        if (! $this->files->isDir(VALET_HOME_PATH.'/Extensions')) {
            return [];
        }

        return collect($this->files->scandir(VALET_HOME_PATH.'/Extensions'))
                    ->reject(function ($file) {
                        return is_dir($file);
                    })
                    ->map(function ($file) {
                        return VALET_HOME_PATH.'/Extensions/'.$file;
                    })
                    ->values()->all();
    }

    /**
     * Determine if this is the latest version of Valet.
     *
     * @param  string  $currentVersion
     * @return bool
     */
    public function onLatestVersion($currentVersion)
    {
        $response = \Httpful\Request::get($this->github)->send();

        return version_compare($currentVersion, trim($response->body->tag_name), '>=');
    }

    /**
     * Determine current environment
     *
     * @return void
     */
    public function environmentSetup()
    {
        $this->packageManagerSetup();
        $this->serviceManagerSetup();
    }

    /**
     * Configure package manager
     *
     * @return void
     */
    public function packageManagerSetup()
    {
        Container::getInstance()->bind(PackageManager::class, $this->getAvailablePackageManager());
    }

    /**
     * Determine the first available package manager
     *
     * @return string
     */
    public function getAvailablePackageManager()
    {
        return collect([
            Apt::class,
            Dnf::class,
            Pacman::class,
            Yum::class,
            PackageKit::class,
            Eopkg::class
        ])->first(function ($pm) {
            return resolve($pm)->isAvailable();
        }, function () {
            throw new DomainException("No compatible package manager found.");
        });
    }

    /**
     * Configure service manager
     *
     * @return void
     */
    public function serviceManagerSetup()
    {
        Container::getInstance()->bind(ServiceManager::class, $this->getAvailableServiceManager());
    }

    /**
     * Determine the first available service manager
     *
     * @return string
     */
    public function getAvailableServiceManager()
    {
        return collect([
            LinuxService::class,
            Systemd::class,
        ])->first(function ($pm) {
            return resolve($pm)->isAvailable();
        }, function () {
            throw new DomainException("No compatible service manager found.");
        });
    }
}
