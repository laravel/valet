<?php

namespace Valet;

class Caddy
{
    var $cli;
    var $files;
    var $daemonPath = '/lib/systemd/system/caddy.service';

    /**
     * Create a new Caddy instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     */
    function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Install the system launch daemon for the Caddy server.
     *
     * @return void
     */
    function install()
    {
        $this->caddyAllowRootPorts();
        $this->installCaddyFile();
        $this->installCaddyDirectory();
        $this->installCaddyDaemon();
    }

    /**
     * Install the Caddyfile to the ~/.valet directory.
     *
     * This file serves as the main server configuration for Valet.
     *
     * @return void
     */
    function caddyAllowRootPorts()
    {
        $caddy_bin = $this->files->realpath(__DIR__.'/../../').'/bin/caddy';

        $this->cli->quietly('setcap cap_net_bind_service=+ep '.$caddy_bin);
    }

    /**
     * Install the Caddyfile to the ~/.valet directory.
     *
     * This file serves as the main server configuration for Valet.
     *
     * @return void
     */
    function installCaddyFile()
    {
        $this->files->putAsUser(
            VALET_HOME_PATH.'/Caddyfile',
            str_replace('VALET_HOME_PATH', VALET_HOME_PATH, $this->files->get(__DIR__.'/../stubs/Caddyfile'))
        );
    }

    /**
     * Install the Caddy configuration directory to the ~/.valet directory.
     *
     * This directory contains all site-specific Caddy definitions.
     *
     * @return void
     */
    function installCaddyDirectory()
    {
        if (! $this->files->isDir($caddyDirectory = VALET_HOME_PATH.'/Caddy')) {
            $this->files->mkdirAsUser($caddyDirectory);
        }

        $this->files->touchAsUser($caddyDirectory.'/.keep');
    }

    /**
     * Install the Caddy daemon on a system level daemon.
     *
     * @return void
     */
    function installCaddyDaemon()
    {
        $contents = str_replace(
            'VALET_PATH', $this->files->realpath(__DIR__.'/../../'),
            $this->files->get(__DIR__.'/../stubs/caddy.service')
        );

        $this->files->put(
            $this->daemonPath, str_replace('VALET_HOME_PATH', VALET_HOME_PATH, $contents)
        );

        $this->cli->quietly('systemctl daemon-reload');
        $this->cli->quietly('systemctl enable caddy.service');
    }

    /**
     * Restart the launch daemon.
     *
     * @return void
     */
    function restart()
    {
        $this->cli->quietly('systemctl stop caddy.service');
        $this->cli->quietly('systemctl start caddy.service');
    }

    /**
     * Stop the launch daemon.
     *
     * @return void
     */
    function stop()
    {
        $this->cli->quietly('systemctl stop caddy.service');
    }

    /**
     * Remove the launch daemon.
     *
     * @return void
     */
    function uninstall()
    {
        $this->stop();
        $this->cli->quietly('systemctl disable caddy.service');
        
        $this->files->unlink($this->daemonPath);
        
        $this->cli->quietly('systemctl daemon-reload');
    }

    /**
     * Show Caddy running status.
     *
     * @return void
     */
    function status()
    {
        passthru('systemctl status caddy.service');
    }
}
