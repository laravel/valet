<?php

namespace Valet;

use Exception;
use Symfony\Component\Process\Process;

class DnsMasq
{
    /**
     * Install and configure DnsMasq.
     *
     * @param  OutputInterface  $output
     * @return void
     */
    public static function install($output)
    {
        if (! static::alreadyInstalled()) {
            static::download($output);
        }

        static::createCustomConfigurationFile();

        static::createResolver();

        quietly('sudo brew services restart dnsmasq');
    }

    /**
     * Determine if DnsMasq is already installed.
     *
     * @return void
     */
    public static function alreadyInstalled()
    {
        $process = new Process('brew list | grep dnsmasq');

        $process->run();

        return strlen(trim($process->getOutput())) > 0;
    }

    /**
     * Download DnsMasq from Brew.
     *
     * @param  OutputInterface  $output
     * @return void
     */
    protected static function download($output)
    {
        $output->writeln('<info>DnsMasq is not installed, installing it now via Brew...</info> 🍻'.PHP_EOL);

        $process = new Process('sudo -u '.$_SERVER['SUDO_USER'].' brew install dnsmasq');

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        if ($process->getExitCode() > 0) {
            throw new Exception('We were unable to install DnsMasq.');
        }

        $output->writeln('');
    }

    /**
     * Append the custom DnsMasq configuration file to the main configuration file.
     *
     * @return void
     */
    protected static function createCustomConfigurationFile()
    {
        $dnsMasqConfigPath = '/Users/'.$_SERVER['SUDO_USER'].'/.valet/dnsmasq.conf';

        copy('/usr/local/opt/dnsmasq/dnsmasq.conf.example', '/usr/local/etc/dnsmasq.conf');

        if (strpos(file_get_contents('/usr/local/etc/dnsmasq.conf'), $dnsMasqConfigPath) === false) {
            file_put_contents('/usr/local/etc/dnsmasq.conf', PHP_EOL.'conf-file='.$dnsMasqConfigPath.PHP_EOL, FILE_APPEND);
        }

        chown('/usr/local/etc/dnsmasq.conf', $_SERVER['SUDO_USER']);

        file_put_contents($dnsMasqConfigPath, 'address=/.dev/127.0.0.1'.PHP_EOL);

        chown($dnsMasqConfigPath, $_SERVER['SUDO_USER']);
    }

    /**
     * Create the resolver file to point the "dev" domain to 127.0.0.1.
     *
     * @return void
     */
    protected static function createResolver()
    {
        if (! is_dir('/etc/resolver')) {
            mkdir('/etc/resolver', 0755);
        }

        file_put_contents('/etc/resolver/dev', 'nameserver 127.0.0.1'.PHP_EOL);
    }
}
