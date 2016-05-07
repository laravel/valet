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
        if (! php_is_installed()) {
            static::download($output);
        }

        static::updateConfiguration();

        static::restart();
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

        run('brew install php70', function ($exitCode, $processOutput) use ($output)  {
            $output->write($processOutput);

            throw new Exception('We were unable to install PHP.');
        });
    }

    /**
     * Update the PHP FPM configuration to use the current user.
     *
     * @return void
     */
    public static function updateConfiguration()
    {
        quietly('sed -i "" -e "s/^user = \_www/user = '.$_SERVER['SUDO_USER'].'/" '.static::fpmConfigPath());

        quietly('sed -i "" -e "s/^group = \_www/group = staff/" '.static::fpmConfigPath());
    }

    /**
     * Restart the PHP FPM process.
     *
     * @return void
     */
    public static function restart()
    {
        static::stop();

        quietly('sudo brew services restart '.linked_php());
    }

    /**
     * Stop the PHP FPM process.
     *
     * @return void
     */
    public static function stop()
    {
        quietly('sudo brew services stop php56');
        quietly('sudo brew services stop php70');
    }

    /**
     * Get the path to the FPM configuration file for the current PHP version.
     *
     * @return string
     */
    protected static function fpmConfigPath()
    {
        if (linked_php() === 'php70') {
            return '/usr/local/etc/php/7.0/php-fpm.d/www.conf';
        } else {
            return '/usr/local/etc/php/5.6/php-fpm.conf';
        }
    }
}
