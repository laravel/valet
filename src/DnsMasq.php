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
        if (! Brew::installed('dnsmasq')) {
            static::download($output);
        }

        static::createCustomConfigurationFile();

        static::createResolver();

        Brew::restartService('dnsmasq');
    }

    /**
     * Download DnsMasq from Brew.
     *
     * @param  OutputInterface  $output
     * @return void
     */
    protected static function download($output)
    {
        $output->writeln('<info>DnsMasq is not installed, installing it now via Brew...</info> 🍻');

        run('brew install dnsmasq', function ($errorOutput) use ($output) {
            $output->write($errorOutput);

            throw new Exception('We were unable to install DnsMasq.');
        });
    }

    /**
     * Append the custom DnsMasq configuration file to the main configuration file.
     *
     * @return void
     */
    protected static function createCustomConfigurationFile()
    {
        $dnsMasqConfigPath = '/Users/'.$_SERVER['SUDO_USER'].'/.valet/dnsmasq.conf';

        if (! file_exists('/usr/local/etc/dnsmasq.conf')) {
            copy('/usr/local/opt/dnsmasq/dnsmasq.conf.example', '/usr/local/etc/dnsmasq.conf');
        }

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

    /**
     * Update the domain used by DnsMasq.
     *
     * @param  string  $oldDomain
     * @param  string  $newDomain
     * @return void
     */
    public static function updateDomain($oldDomain, $newDomain)
    {
        quietly('rm /etc/resolver/'.$oldDomain);

        file_put_contents('/etc/resolver/'.$newDomain, 'nameserver 127.0.0.1'.PHP_EOL);

        file_put_contents(VALET_HOME_PATH.'/dnsmasq.conf', 'address=/.'.$newDomain.'/127.0.0.1'.PHP_EOL);

        Brew::restartService('dnsmasq');
    }
}
