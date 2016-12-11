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
        if (! $this->ubuntu->installed(get_config('php71')['name']) &&
            ! $this->ubuntu->installed(get_config('php70')['name']) &&
            ! $this->ubuntu->installed(get_config('php56')['name']) &&
            ! $this->ubuntu->installed(get_config('php55')['name'])) {
            $this->ubuntu->ensureInstalled(get_config('php70')['name']);
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
        $contents = preg_replace('/^listen.owner = .+$/m', 'listen.owner = '.user(), $contents);
        $contents = preg_replace('/^listen = .+$/m', 'listen = '.VALET_HOME_PATH.'/valet.sock', $contents);

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
        $this->ubuntu->stopService($this->ubuntu->linkedPhp()['service']);
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
