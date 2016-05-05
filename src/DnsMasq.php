<?php

namespace Valet;

use Exception;
use Symfony\Component\Process\Process;

class DnsMasq
{
    const UBUNTU_INSTALL = 'sudo %s apt-get install dnsmasq';
    const UBUNTU_ALREADY_INSTALLED = 'which dnsmasq';
    const INSTALLING_DNSMASQ = '<info>DnsMasq is not installed, installing it now via apt...</info> 🍻';
    const RESTART_DNSMASQ = 'sudo service dnsmasq restart';
    const UBUNTU_ROOT_USER = '/%s/.valet/dnsmasq.conf';
    const DNSMASQ_CONF_EXAMPLE = '/etc/dnsmasq.d/dnsmasq.conf.example';
    const DNSMASQ_CONF = '/etc/dnsmasq.conf';

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

        quietly(self::RESTART_DNSMASQ);
    }

    /**
     * Determine if DnsMasq is already installed.
     *
     * @return void
     */
    public static function alreadyInstalled()
    {
        $process = new Process(self::UBUNTU_ALREADY_INSTALLED);

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
        $output->writeln(self::INSTALLING_DNSMASQ);

        $process = new Process(sprintf(self::UBUNTU_INSTALL, $_SERVER['SUDO_USER']));

        $processOutput = '';
        $process->run(function ($type, $line) use (&$processOutput) {
            $processOutput .= $line;
        });

        if ($process->getExitCode() > 0) {
            $output->write($processOutput);

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
        $dnsMasqConfigPath = sprintf(self::UBUNTU_ROOT_USER, $_SERVER['SUDO_USER']);

        @mkdir(dirname($dnsMasqConfigPath), 0755, true);


        if (strpos(file_get_contents(self::DNSMASQ_CONF), $dnsMasqConfigPath) === false) {
            file_put_contents(self::DNSMASQ_CONF, PHP_EOL.'conf-file='.$dnsMasqConfigPath.PHP_EOL, FILE_APPEND);
        }

        chown(self::DNSMASQ_CONF, $_SERVER['SUDO_USER']);

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
