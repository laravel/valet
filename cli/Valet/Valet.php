<?php

namespace Valet;

use DomainException;
use Illuminate\Container\Container;
use Valet\Contracts\PackageManager;
use Valet\Contracts\ServiceManager;
use Valet\PackageManagers\Apt;
use Valet\PackageManagers\Dnf;
use Valet\ServiceManagers\LinuxService;

class Valet
{
    var $cli, $files;

    var $valetBin = '/usr/local/bin/valet';

    /**
     * Create a new Valet instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     */
    function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Symlink the Valet Bash script into the user's local bin.
     *
     * @return void
     */
    function symlinkToUsersBin()
    {
        $this->cli->run('ln -snf '.realpath(__DIR__.'/../../valet').' '.$this->valetBin);
    }

    /**
     * Create the "sudoers.d" entry for running Valet.
     *
     * @return void
     */
    function createSudoersEntry()
    {
        $this->files->ensureDirExists('/etc/sudoers.d');

        $this->files->put('/etc/sudoers.d/valet', 'Cmnd_Alias VALET = /usr/local/bin/valet *
%sudo ALL=(root) NOPASSWD: VALET'.PHP_EOL.'
%wheel ALL=(root) NOPASSWD: VALET'.PHP_EOL);

        $this->cli->quietly('chmod 0440 /etc/sudoers.d/valet');
    }

    /**
     * Get the paths to all of the Valet extensions.
     *
     * @return array
     */
    function extensions()
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
    function onLatestVersion($currentVersion)
    {
        $response = \Httpful\Request::get('https://api.github.com/repos/cpriego/valet-ubuntu/releases/latest')->send();

        return version_compare($currentVersion, trim($response->body->tag_name, 'v'), '>=');
    }

    /**
     * Determine current environment
     *
     * @return void
     */
    function environmentSetup()
    {
        $this->packageManagerSetup();
        $this->serviceManagerSetup();
    }

    /**
     * Configure package manager
     *
     * @return void
     */
    function packageManagerSetup()
    {
        Container::getInstance()->bind(PackageManager::class, $this->getAvailablePackageManager());
    }

    /**
     * Determine the first available package manager
     *
     * @return string
     */
    function getAvailablePackageManager()
    {
        return collect([
            Apt::class,
            Dnf::class,
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
    function serviceManagerSetup()
    {
        Container::getInstance()->bind(ServiceManager::class, $this->getAvailableServiceManager());
    }

    /**
     * Determine the first available service manager
     *
     * @return string
     */
    function getAvailableServiceManager()
    {
        return collect([
            LinuxService::class,
        ])->first(function ($pm) {
            return resolve($pm)->isAvailable();
        }, function () {
            throw new DomainException("No compatible service manager found.");
        });
    }
}
