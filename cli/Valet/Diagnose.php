<?php

namespace Valet;

class Diagnose
{
    var $commands = [
        'sw_vers',
        'valet --version',
        'cat ~/.config/valet/config.json',
        'cat ~/.composer/composer.json',
        'composer global diagnose',
        'ls -al /etc/sudoers.d/',
        'brew config',
        'brew services list',
        'brew list --versions | grep -E "(php|nginx|dnsmasq|mariadb|mysql|mailhog|openssl)(@\d\..*)?\s"',
        'php -v',
        'which -a php',
        'php --ini',
        'nginx -v',
        'curl --version',
        'php --ri curl',
        '~/.composer/vendor/laravel/valet/bin/ngrok version',
        'ls -al ~/.ngrok2',
        'brew info nginx',
        'brew info php',
        'brew info openssl',
        'openssl version -a',
        'openssl ciphers',
        'nginx -t',
        'which -a php-fpm',
        '/usr/local/opt/php/sbin/php-fpm --test',
        '/usr/local/opt/php/sbin/php-fpm -y /usr/local/etc/php/7.4/php-fpm.conf --test',
        'ls -al ~/Library/LaunchAgents',
        'ls -al /Library/LaunchAgents',
        'ls -al /Library/LaunchDaemons',
    ];

    var $cli, $files;

    /**
     * Create a new Diagnose instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Run diagnostics.
     */
    function run($print)
    {
        $result = collect($this->commands)->map(function ($command) use ($print) {
            $output = $this->cli->runAsUser($command);

            if ($print) {
                output(str_repeat('-', 25));
                info("$ $command");
                output(trim($output));
            }

            return implode(PHP_EOL, ["$ $command", trim($output)]);
        })->implode(PHP_EOL.str_repeat('-', 25).PHP_EOL);

        $this->files->put('valet_diagnostics.txt', $result);

        $this->cli->run('pbcopy < valet_diagnostics.txt');

        $this->files->unlink('valet_diagnostics.txt');
    }
}
