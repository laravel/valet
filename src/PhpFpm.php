<?php

namespace Valet;

use Exception;
use Symfony\Component\Process\Process;

class PhpFpm
{
    /**
     * Install and configure DnsMasq.
     *
     * @param  OutputInterface  $output
     * @return void
     */
    public static function install($output)
    {
        if (! static::alreadyInstalled()) {
            static::download($output);
        }

        static::updateConfiguration();

        static::restart();
    }

    /**
     * Determine if DnsMasq is already installed.
     *
     * @return void
     */
    public static function alreadyInstalled()
    {
        $process = new Process('brew list | grep php70');

        $process->run();

        return in_array('php70', explode(PHP_EOL, $process->getOutput()));
    }

    /**
     * Download a fresh copy of PHP 7.0 from Brew.
     *
     * @param  OutputInterface  $output
     * @return void
     */
    public static function download($output)
    {
        $output->writeln('<info>PHP 7.0 is not installed, installing it now via Brew...</info> 🍻');

        passthru('sudo -u '.$_SERVER['SUDO_USER'].' brew tap homebrew/dupes');
        passthru('sudo -u '.$_SERVER['SUDO_USER'].' brew tap homebrew/versions');
        passthru('sudo -u '.$_SERVER['SUDO_USER'].' brew tap homebrew/homebrew-php');

        $process = new Process('sudo -u '.$_SERVER['SUDO_USER'].' brew install php70');

        $processOutput = '';
        $process->run(function ($type, $line) use (&$processOutput) {
            $processOutput .= $line;
        });

        if ($process->getExitCode() > 0) {
            $output->write($processOutput);

            throw new Exception('We were unable to install PHP.');
        }

        $output->writeln('');
    }

    /**
     * Update the PHP FPM configuration to use the current user.
     *
     * @return void
     */
    public static function updateConfiguration()
    {
        quietly('sed -i "" -e "s/^user = \_www/user = '.$_SERVER['SUDO_USER'].'/" /usr/local/etc/php/7.0/php-fpm.d/www.conf');

        quietly('sed -i "" -e "s/^group = \_www/group = staff/" /usr/local/etc/php/7.0/php-fpm.d/www.conf');
    }

    /**
     * Restart the PHP FPM process.
     *
     * @return void
     */
    public static function restart()
    {
        quietly('sudo brew services restart php70');
    }

    /**
     * Stop the PHP FPM process.
     *
     * @return void
     */
    public static function stop()
    {
        quietly('sudo brew services stop php70');
    }
}
