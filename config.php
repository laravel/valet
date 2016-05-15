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
        
        // Latest PHP
        "php-latest" => "php7.0",
        "fpm-service" => "php7.0-fpm",
        "fpm-config" => "/etc/php/7.0/fpm/pool.d/www.conf",
        
        // Caddy/Systemd
        "systemd-caddy" => "/lib/systemd/system/caddy.service",
        "systemd-caddy-fpm" => "/var/run/php/php7.0-fpm.sock",
        
        // PHP 5.6
        "php-56" => "php5.6",
        "fpm56-service" => "php5.6-fpm",
        "fpm56-config" => "/etc/php/5.6/php-fpm.conf",
        
        // PHP 5.5
        "php-55" => "php5.5",
        "fpm55-service" => "php5.5-fpm",
        "fpm55-config" => "/etc/php/5.5/php-fpm.conf",
    ];

    return $config[$value];
}