<?php

namespace Valet;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

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
        'sudo nginx -t',
        'which -a php-fpm',
        'sudo /usr/local/opt/php/sbin/php-fpm --test',
        'sudo /usr/local/opt/php/sbin/php-fpm -y /usr/local/etc/php/7.4/php-fpm.conf --test',
        'ls -al ~/Library/LaunchAgents',
        'ls -al /Library/LaunchAgents',
        'ls -al /Library/LaunchDaemons',
    ];

    var $cli, $files, $print, $progressBar;

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
        $this->print = $print;

        $this->beforeRun();

        $result = collect($this->commands)->map(function ($command) {
            $this->beforeCommand($command);

            $output = $this->cli->runAsUser($command);

            $this->afterCommand($command, $output);

            return implode(PHP_EOL, ["$ $command", trim($output)]);
        })->implode(PHP_EOL.str_repeat('-', 25).PHP_EOL);

        $this->files->put('valet_diagnostics.txt', $result);

        $this->cli->run('pbcopy < valet_diagnostics.txt');

        $this->files->unlink('valet_diagnostics.txt');

        $this->afterRun();
    }

    function beforeRun()
    {
        if ($this->print) {
            return;
        }

        $this->progressBar = new ProgressBar(new ConsoleOutput, count($this->commands));

        $this->progressBar->start();
    }

    function afterRun()
    {
        if ($this->progressBar) {
            $this->progressBar->finish();
        }

        output('');
    }

    function beforeCommand($command)
    {
        if ($this->print) {
            info(PHP_EOL."$ $command");
        }
    }

    function afterCommand($command, $output)
    {
        if ($this->print) {
            output(trim($output));
        } else {
            $this->progressBar->advance();
        }
    }
}
