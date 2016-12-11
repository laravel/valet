<?php

namespace Valet;

use DomainException;
use Valet\Contracts\PackageManager;
use Valet\Contracts\ServiceManager;

class PhpFpm
{
    var $pm, $cli, $files;

    /**
     * Create a new PHP FPM class instance.
     *
     * @param  PackageManager $pm
     * @param  ServiceManager $sm
     * @param  CommandLine $cli
     * @param  Filesystem $files
     * @return void
     */
    function __construct(PackageManager $pm, ServiceManager $sm, CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->pm = $pm;
        $this->sm = $sm;
        $this->files = $files;
    }

    /**
     * Install and configure PHP-FPM.
     *
     * @return void
     */
    function install()
    {
        $this->pm->ensureInstalled('php');

        $this->files->ensureDirExists(log_dir(), user());

        $this->updateConfiguration();

        $this->restart();
    }

    /**
     * Update the PHP FPM configuration.
     *
     * @return void
     */
    function updateConfiguration()
    {
        $contents = $this->files->get($this->fpmConfigPath());

        $contents = preg_replace('/^user = .+$/m', 'user = '.user(), $contents);
        $contents = preg_replace('/^group = .+$/m', 'group = '.group(), $contents);
        $contents = preg_replace('/^listen = .+$/m', 'listen = '.VALET_HOME_PATH.'/valet.sock', $contents);
        $contents = preg_replace('/^;?listen\.owner = .+$/m', 'listen.owner = '.user(), $contents);
        $contents = preg_replace('/^;?listen\.group = .+$/m', 'listen.group = '.group(), $contents);
        $contents = preg_replace('/^;?listen\.mode = .+$/m', 'listen.mode = 0777', $contents);

        $this->files->put($this->fpmConfigPath(), $contents);
    }

    /**
     * Restart the PHP FPM process.
     *
     * @return void
     */
    function restart()
    {
        $this->sm->restart('php');
    }

    /**
     * Get the path to the FPM configuration file for the current PHP version.
     *
     * @return string
     */
    function fpmConfigPath()
    {
        $phpVersion = substr(PHP_VERSION, 0, 3);

        return collect([
            etc_dir('php/' . $phpVersion . '/php-fpm.d/www.conf'), // OSX >=7.0
            etc_dir('php/' . $phpVersion . '/php-fpm.conf'), // OSX <=5.6
            etc_dir('php/' . $phpVersion . '/fpm/pool.d/www.conf'), // Ubuntu
            etc_dir('php-fpm.d/www.conf'), // Fedora
        ])->first(function ($path) {
            return file_exists($path);
        }, function () {
            throw new DomainException("Unable to determine PHP-FPM configuration file.");
        });
    }
}
