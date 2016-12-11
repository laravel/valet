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

        // PHP 7.1
        "php71" => [
            "bin" => "php7.1",
            "cli" => "php7.1-cli",
            "fpm" => "php7.1-fpm",
            "fpm-config" => "/etc/php/7.1/fpm/pool.d/valet.conf",
        ],

        // PHP 7.0
        "php70" => [
            "bin" => "php7.0",
            "cli" => "php7.0-cli",
            "fpm" => "php7.0-fpm",
            "fpm-config" => "/etc/php/7.0/fpm/pool.d/valet.conf"
        ],

        // PHP 5.6
        "php56" => [
            "bin" => "php5.6",
            "cli" => "php5.6-cli",
            "fpm" => "php5.6-fpm",
            "fpm-config" => "/etc/php/5.6/fpm/pool.d/valet.conf"
        ],

        // PHP 5.5.38 (Ondrej PPA)
        "php55" => [
            "bin" => "php5.5",
            "cli" => "php5.5-cli",
            "fpm" => "php5.5-fpm",
            "fpm-config" => "/etc/php/5.5/fpm/pool.d/valet.conf"
        ],

        // PHP 5.5.9 (Ubuntu 14.04 default)
        "php5" => [
            "bin" => "php5",
            "cli" => "php5-cli",
            "fpm" => "php5-fpm",
            "fpm-config" => "/etc/php5/fpm/pool.d/valet.conf"
        ],
    ];

    return $config[$value];
}
