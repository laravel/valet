<?php

namespace Valet;

class Caddy
{
    /**
     * Install the system launch daemon for the Node proxy.
     *
     * @return void
     */
    public static function install()
    {
        file_put_contents(
            VALET_HOME_PATH.'/Caddyfile',
            str_replace('USER', $_SERVER['SUDO_USER'], file_get_contents(__DIR__.'/../stubs/Caddyfile'))
        );

        chown(VALET_HOME_PATH.'/Caddyfile', $_SERVER['SUDO_USER']);

        if (! is_dir($caddyDirectory = VALET_HOME_PATH.'/Caddy')) {
            mkdir($caddyDirectory, 0755);

            chown($caddyDirectory, $_SERVER['SUDO_USER']);

            touch($caddyDirectory.'/.keep');

            chown($caddyDirectory.'/.keep', $_SERVER['SUDO_USER']);
        }

        $contents = str_replace(
            'VALET_PATH', realpath(__DIR__.'/../'), file_get_contents(__DIR__.'/../stubs/daemon.plist')
        );

        $contents = str_replace('VALET_HOME_PATH', VALET_HOME_PATH, $contents);

        file_put_contents('/Library/LaunchDaemons/com.laravel.valetServer.plist', $contents);
    }

    /**
     * Restart the launch daemon.
     *
     * @return void
     */
    public static function restart()
    {
        quietly('launchctl unload /Library/LaunchDaemons/com.laravel.valetServer.plist > /dev/null');

        exec('launchctl load /Library/LaunchDaemons/com.laravel.valetServer.plist');
    }

    /**
     * Stop the launch daemon.
     *
     * @return void
     */
    public static function stop()
    {
        quietly('launchctl unload /Library/LaunchDaemons/com.laravel.valetServer.plist > /dev/null');
    }

    /**
     * Remove the launch daemon.
     *
     * @return void
     */
    public static function uninstall()
    {
        static::stop();

        unlink('/Library/LaunchDaemons/com.laravel.valetServer.plist');
    }
}
