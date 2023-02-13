<?php

namespace Valet;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class Diagnose
{
    public $commands = [
        'sw_vers',
        'valet --version',
        'cat ~/.config/valet/config.json',
        'cat ~/.composer/composer.json',
        'composer global diagnose',
        'composer global outdated',
        'ls -al /etc/sudoers.d/',
        'brew update > /dev/null 2>&1',
        'brew config',
        'brew services list',
        'brew list --formula --versions | grep -E "(php|nginx|dnsmasq|mariadb|mysql|mailhog|openssl)(@\d\..*)?\s"',
        'brew outdated',
        'brew tap',
        'php -v',
        'which -a php',
        'php --ini',
        'nginx -v',
        'curl --version',
        'php --ri curl',
        BREW_PREFIX.'/bin/ngrok version',
        'ls -al ~/.ngrok2',
        'brew info nginx',
        'brew info php',
        'brew info openssl',
        'openssl version -a',
        'openssl ciphers',
        'sudo nginx -t',
        'which -a php-fpm',
        BREW_PREFIX.'/opt/php/sbin/php-fpm -v',
        'sudo '.BREW_PREFIX.'/opt/php/sbin/php-fpm -y '.PHP_SYSCONFDIR.'/php-fpm.conf --test',
        'ls -al ~/Library/LaunchAgents | grep homebrew',
        'ls -al /Library/LaunchAgents | grep homebrew',
        'ls -al /Library/LaunchDaemons | grep homebrew',
        'ls -al /Library/LaunchDaemons | grep "com.laravel.valet."',
        'ls -aln /etc/resolv.conf',
        'cat /etc/resolv.conf',
        'ifconfig lo0',
        'sh -c \'echo "------\n'.BREW_PREFIX.'/etc/nginx/valet/valet.conf\n---\n"; cat '.BREW_PREFIX.'/etc/nginx/valet/valet.conf | grep -n "# valet loopback"; echo "\n------\n"\'',
        'sh -c \'for file in ~/.config/valet/dnsmasq.d/*; do echo "------\n~/.config/valet/dnsmasq.d/$(basename $file)\n---\n"; cat $file; echo "\n------\n"; done\'',
        'sh -c \'for file in ~/.config/valet/nginx/*; do echo "------\n~/.config/valet/nginx/$(basename $file)\n---\n"; cat $file | grep -n "# valet loopback"; echo "\n------\n"; done\'',
    ];

    public $print;

    public $progressBar;

    public function __construct(public CommandLine $cli, public Filesystem $files)
    {
    }

    /**
     * Run diagnostics.
     */
    public function run(bool $print, bool $plainText): void
    {
        $this->print = $print;

        $this->beforeRun();

        $results = collect($this->commands)->map(function ($command) {
            $this->beforeCommand($command);

            $output = $this->runCommand($command);

            if ($this->ignoreOutput($command)) {
                return;
            }

            $this->afterCommand($command, $output);

            return compact('command', 'output');
        })->filter()->values();

        $output = $this->format($results, $plainText);

        $this->files->put('valet_diagnostics.txt', $output);

        $this->cli->run('pbcopy < valet_diagnostics.txt');

        $this->files->unlink('valet_diagnostics.txt');

        $this->afterRun();
    }

    public function beforeRun(): void
    {
        if ($this->print) {
            return;
        }

        $this->progressBar = new ProgressBar(new ConsoleOutput, count($this->commands));

        $this->progressBar->start();
    }

    public function afterRun(): void
    {
        if ($this->progressBar) {
            $this->progressBar->finish();
        }

        output('');
    }

    public function runCommand(string $command): string
    {
        return strpos($command, 'sudo ') === 0
            ? $this->cli->run($command)
            : $this->cli->runAsUser($command);
    }

    public function beforeCommand(string $command): void
    {
        if ($this->print) {
            info(PHP_EOL."$ $command");
        }
    }

    public function afterCommand(string $command, string $output): void
    {
        if ($this->print) {
            output(trim($output));
        } else {
            $this->progressBar->advance();
        }
    }

    public function ignoreOutput(string $command): bool
    {
        return strpos($command, '> /dev/null 2>&1') !== false;
    }

    public function format(Collection $results, bool $plainText): string
    {
        return $results->map(function ($result) use ($plainText) {
            $command = $result['command'];
            $output = trim($result['output']);

            if ($plainText) {
                return implode(PHP_EOL, ["$ {$command}", $output]);
            }

            return sprintf(
                '<details>%s<summary>%s</summary>%s<pre>%s</pre>%s</details>',
                PHP_EOL, $command, PHP_EOL, $output, PHP_EOL
            );
        })->implode($plainText ? PHP_EOL.str_repeat('-', 20).PHP_EOL : PHP_EOL);
    }
}
