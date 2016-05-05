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
            'SERVER_PATH', 
            realpath(__DIR__.'/../server.php'),
            file_get_contents(__DIR__.Compatibility::get('LAUNCH_DAEMON_INSTALL_SCRIPT'))
        );

        $contents = str_replace('PHP_PATH', exec('which php'), $contents);
        
        file_put_contents(Compatibility::get('LAUNCH_DAEMON_INSTALL_PATH'), $contents);

        quietly(Compatibility::get('LAUNCH_DAEMON_QUIETLY_START'));
    }

    /**
     * Restart the launch daemon.
     *
     * @return void
     */
    public static function restart()
    {
        quietly(Compatibility::get('LAUNCH_DAEMON_QUIETLY_RESTART'));

        exec(Compatibility::get('LAUNCH_DAEMON_RESTART'));
    }

    /**
     * Stop the launch daemon.
     *
     * @return void
     */
    public static function stop()
    {
        quietly(Compatibility::get('LAUNCH_DAEMON_STOP'));
    }

    /**
     * Remove the launch daemon.
     *
     * @return void
     */
    public static function uninstall()
    {
        static::stop();
        
        unlink(Compatibility::get('LAUNCH_DAEMON_UNLINK'));
    }
}
