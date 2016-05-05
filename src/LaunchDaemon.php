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
/*
//This is for upstart linux...

        $script = <<<SH
description "Laravel Valet for Ubuntu"
author "Nil Portugues Caldero <contact@nilportugues.com>"

start on startup
stop on shutdown
respawn

script
 $(which php) -S 127.0.0.1:80 SERVER_PATH
end script

SH;
*/

        $script = <<<SH
[Unit]
Description=Laravel Valet for Ubuntu

[Service]
ExecStart=/usr/bin/php -S 127.0.0.1:80 SERVER_PATH
Restart=on-abort

[Install]
WantedBy=multi-user.target
SH;


        $contents = str_replace(
            'SERVER_PATH', realpath(__DIR__.'/../server.php'), $script
        );

        $contents = str_replace('PHP_PATH', exec('which php'), $contents);


        //file_put_contents('/etc/init/laravel-valetd', $contents); //for upstart users
        file_put_contents('/etc/systemd/system/laravel-valetd.service', $contents);
        exec('sudo systemctl start laravel-valetd');
    }

    /**
     * Restart the launch daemon.
     *
     * @return void
     */
    public static function restart()
    {
        //exec('stop laravel-valetd && start laravel-valetd');
        exec('systemctl stop laravel-valetd && systemctl start laravel-valetd');
    }

    /**
     * Stop the launch daemon.
     *
     * @return void
     */
    public static function stop()
    {
        exec('systemctl stop laravel-valetd');
    }

    /**
     * Remove the launch daemon.
     *
     * @return void
     */
    public static function uninstall()
    {
        static::stop();

        //unlink('/etc/init/laravel-valetd');
        unlink('/etc/systemd/system/laravel-valetd.service');
    }
}
