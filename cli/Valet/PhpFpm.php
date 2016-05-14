<?php

namespace Valet;

use Exception;
use DomainException;
use Symfony\Component\Process\Process;

class PhpFpm
{
    public $ubuntu, $cli, $files;

    /**
     * Create a new PHP FPM class instance.
     *
     * @param  Ubuntu  $ubuntu
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    public function __construct(Ubuntu $ubuntu, CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->ubuntu = $ubuntu;
        $this->files = $files;
    }

    /**
     * Install and configure DnsMasq.
     *
     * @return void
     */
    public function install()
    {
        if (! $this->ubuntu->installed('php7.0') &&
            ! $this->ubuntu->installed('php5.6') &&
            ! $this->ubuntu->installed('php5.5')) {
            $this->ubuntu->ensureInstalled('php7.0');
        }

        $this->files->ensureDirExists('/var/log', user());

        $this->updateConfiguration();

        $this->restart();
    }

    /**
     * Update the PHP FPM configuration to use the current user.
     *
     * @return void
     */
    public function updateConfiguration()
    {
        $contents = $this->files->get($this->fpmConfigPath());

        $contents = preg_replace('/^user = .+$/m', 'user = '.user(), $contents);
        $contents = preg_replace('/^group = .+$/m', 'group = staff', $contents);

        $this->files->put($this->fpmConfigPath(), $contents);
    }

    /**
     * Restart the PHP FPM process.
     *
     * @return void
     */
    public function restart()
    {
        $this->stop();

        $this->ubuntu->restartLinkedPhp();
    }

    /**
     * Stop the PHP FPM process.
     *
     * @return void
     */
    public function stop()
    {
        $this->ubuntu->stopService('php5.5', 'php5.6', 'php7.0');
    }

    /**
     * Get the path to the FPM configuration file for the current PHP version.
     *
     * @return string
     */
    public function fpmConfigPath()
    {
        if ($this->ubuntu->linkedPhp() === 'php7.0') {
            return '/etc/php/7.0/fpm/php-fpm.conf';
        } elseif ($this->ubuntu->linkedPhp() === 'php5.6') {
            return '/etc/php/5.6/php-fpm.conf';
        } elseif ($this->ubuntu->linkedPhp() === 'php5.5') {
            return '/etc/php/5.5/php-fpm.conf';
        } else {
            throw new DomainException('Unable to find php-fpm config.');
        }
    }
}
