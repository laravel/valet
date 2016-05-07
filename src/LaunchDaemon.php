<?php

namespace Valet;

class LaunchDaemon
{
    /**
     * Install the system launch daemon for the Node proxy.
     *
     * @return void
     */
    public static function install()
    {
        $contents = str_replace(
            'VALET_PATH', realpath(__DIR__.'/../'), file_get_contents(__DIR__.'/../stubs/caddyDaemon.plist')
        );

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
