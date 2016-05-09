<?php

namespace Valet;

use Exception;
use Symfony\Component\Process\Process;

class PhpFpm
{
    var $brew, $cli;

    var $taps = [
        'homebrew/dupes', 'homebrew/versions', 'homebrew/homebrew-php'
    ];

    /**
     * Create a new PHP FPM class instance.
     *
     * @param  Brew  $brew
     * @param  CommandLine  $cli
     * @return void
     */
    function __construct(Brew $brew, CommandLine $cli)
    {
        $this->cli = $cli;
        $this->brew = $brew;
    }

    /**
     * Install and configure DnsMasq.
     *
     * @return void
     */
    function install()
    {
        $this->brew->ensureInstalled('php70', $this->taps);

        $this->updateConfiguration();

        $this->restart();
    }

    /**
     * Update the PHP FPM configuration to use the current user.
     *
     * @return void
     */
    function updateConfiguration()
    {
        $this->cli->quietly('sed -i "" -E "s/^user = .+$/user = '.user().'/" '.$this->fpmConfigPath());

        $this->cli->quietly('sed -i "" -E "s/^group = .+$/group = staff/" '.$this->fpmConfigPath());
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
        $this->brew->stopService('php56', 'php70');
    }

    /**
     * Get the path to the FPM configuration file for the current PHP version.
     *
     * @return string
     */
    function fpmConfigPath()
    {
        if ($this->brew->linkedPhp() === 'php70') {
            return '/usr/local/etc/php/7.0/php-fpm.d/www.conf';
        } else {
            return '/usr/local/etc/php/5.6/php-fpm.conf';
        }
    }
}
