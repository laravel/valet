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
        if (! $this->ubuntu->installed(get_config('php71')['fpm']) &&
            ! $this->ubuntu->installed(get_config('php70')['fpm']) &&
            ! $this->ubuntu->installed(get_config('php56')['fpm']) &&
            ! $this->ubuntu->installed(get_config('php55')['fpm']) &&
            ! $this->ubuntu->installed(get_config('php5')['fpm'])) {
            $this->ubuntu->ensureInstalled(get_config('php70')['fpm']);
        }

        $this->files->ensureDirExists('/var/log', user());

        $this->installConfiguration();

        $this->restart();
    }

    /**
     * Update the PHP FPM configuration to use the current user.
     *
     * @return void
     */
    public function installConfiguration()
    {
        $contents = $this->files->get(__DIR__.'/../stubs/fpm.conf');

        $this->files->putAsUser(
            $this->fpmConfigPath(),
            str_replace(['VALET_USER', 'VALET_HOME_PATH'], [user(), VALET_HOME_PATH], $contents)
        );
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
        $this->ubuntu->stopService($this->ubuntu->linkedPhp()['fpm']);
    }

    /**
     * Get the path to the FPM configuration file for the current PHP version.
     *
     * @return string
     */
    public function fpmConfigPath()
    {
        return $this->ubuntu->linkedPhp()['fpm-config'];
    }
}
