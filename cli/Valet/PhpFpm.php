<?php

namespace Valet;

use Exception;
use DomainException;
use Symfony\Component\Process\Process;

class PhpFpm
{
    var $brew, $cli, $files;

    var $taps = [
        'homebrew/dupes', 'homebrew/versions', 'homebrew/homebrew-php'
    ];

    /**
     * Create a new PHP FPM class instance.
     *
     * @param  Brew  $brew
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    function __construct(Brew $brew, CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->brew = $brew;
        $this->files = $files;
    }

    /**
     * Install and configure DnsMasq.
     *
     * @return void
     */
    function install()
    {
        if (! $this->brew->installed('php71') &&
            ! $this->brew->installed('php70') &&
            ! $this->brew->installed('php56') &&
            ! $this->brew->installed('php55')) {
            $this->brew->ensureInstalled('php70', $this->taps);
        }

        $this->files->ensureDirExists('/usr/local/var/log', user());

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
        $contents = preg_replace('/^group = .+$/m', 'group = staff', $contents);
        $contents = preg_replace('/^listen = .+$/m', 'listen = /var/run/fpm-valet.socket', $contents);

        $this->files->put($this->fpmConfigPath(), $contents);
    }

    /**
     * Restart the PHP FPM process.
     *
     * @return void
     */
    function restart()
    {
        $this->stop();

        $this->brew->restartLinkedPhp();
    }

    /**
     * Stop the PHP FPM process.
     *
     * @return void
     */
    function stop()
    {
        $this->brew->stopService('php55', 'php56', 'php70', 'php71');
    }

    /**
     * Get the path to the FPM configuration file for the current PHP version.
     *
     * @return string
     */
    function fpmConfigPath()
    {
        $confLookup = [
            'php71' => '/usr/local/etc/php/7.1/php-fpm.d/www.conf',
            'php70' => '/usr/local/etc/php/7.0/php-fpm.d/www.conf',
            'php56' => '/usr/local/etc/php/5.6/php-fpm.conf',
            'php55' => '/usr/local/etc/php/5.5/php-fpm.conf',
        ];

        return $confLookup[$this->brew->linkedPhp()];
    }

    /**
     * Get the path to Valet's PHP ini file for the current PHP version.
     *
     * @return string
     */
    function fpmIniPath()
    {
        $versionLookup = [
            'php71' => '/usr/local/etc/php/7.1/conf.d/valet.ini',
            'php70' => '/usr/local/etc/php/7.0/conf.d/valet.ini',
            'php56' => '/usr/local/etc/php/5.6/conf.d/valet.ini',
            'php55' => '/usr/local/etc/php/5.5/conf.d/valet.ini',
        ];

        return $versionLookup[$this->brew->linkedPhp()];
    }
}
