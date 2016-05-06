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
            'SERVER_PATH', realpath(__DIR__ . '/../server.php'), file_get_contents(__DIR__ . '/../stubs/daemon.plist')
        );

        $contents = str_replace('PHP_PATH', exec('which php'), $contents);

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
     * Check for an existing Valet daemon.
     *
     * @return string | null
     */
    public static function status()
    {
        return exec('launchctl list | grep valetServer');
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
