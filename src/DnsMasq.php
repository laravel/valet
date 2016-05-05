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

        quietly(Compatibility::get('DNSMASQ_RESTART'));
    }

    /**
     * Determine if DnsMasq is already installed.
     *
     * @return void
     */
    public static function alreadyInstalled()
    {
        $process = new Process(Compatibility::get('DNSMASQ_ALREADY_INSTALLED'));

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
        $output->writeln(Compatibility::get('DNSMASQ_INSTALL_TEXT'));

        $process = new Process(sprintf(Compatibility::get('DNSMASQ_INSTALL'), $_SERVER['SUDO_USER']));

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
        $dnsMasqConfigPath = sprintf(Compatibility::get('DNSMASQ_ROOT_USER'), $_SERVER['SUDO_USER']);

        if(!file_exists(dirname($dnsMasqConfigPath))) {
            mkdir(dirname($dnsMasqConfigPath), 0755, true);
        }

        $dnsMasqConfig = Compatibility::get('DNSMASQ_CONF');

        if (strpos(file_get_contents($dnsMasqConfig), $dnsMasqConfigPath) === false) {
            file_put_contents($dnsMasqConfig, PHP_EOL.'conf-file='.$dnsMasqConfigPath.PHP_EOL, FILE_APPEND);
        }

        chown($dnsMasqConfig, $_SERVER['SUDO_USER']);

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
