<?php

/**
 * Output the given text to the console.
 *
 * @param  string  $output
 * @return void
 */
function get_config($value)
{
    $config = [
        // PHP binary path
        "php-bin" => "/usr/bin/php",

        // Systemd
        "systemd-fpm" => "/var/run/php/php7.0-fpm.sock",

        // PHP 7.1
        "php71" => [
            "name" => "php7.1",
            "service" => "php7.1-fpm",
            "fpm-config" => "/etc/php/7.1/fpm/pool.d/www.conf",
        ],

        // PHP 7.0
        "php70" => [
            "name" => "php7.0",
            "service" => "php7.0-fpm",
            "fpm-config" => "/etc/php/7.0/fpm/pool.d/www.conf"
        ],

        // PHP 5.6
        "php56" => [
            "name" => "php5.6",
            "service" => "php5.6-fpm",
            "fpm-config" => "/etc/php/5.6/php-fpm.conf"
        ],

        // PHP 5.5
        "php55" => [
            "name" => "php5.5",
            "service" => "php5.5-fpm",
            "fpm-config" => "/etc/php/5.5/php-fpm.conf"
        ],
    ];

    return $config[$value];
}
