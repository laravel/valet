<?php

namespace Valet;

class Caddy
{
    var $cli;
    var $files;
    var $configuration;
    var $site;
    var $daemonPath = '/Library/LaunchDaemons/com.laravel.valetServer.plist';

    /**
     * Create a new Brew instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     */
    function __construct(CommandLine $cli, Filesystem $files, Configuration $configuration, Site $site)
    {
        $this->cli = $cli;
        $this->files = $files;
        $this->configuration = $configuration;
        $this->site = $site;
    }

    /**
     * Install the system launch daemon for the Caddy server.
     *
     * @return void
     */
    function install()
    {
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
    function installCaddyFile()
    {
        $this->files->putAsUser(
            VALET_HOME_PATH.'/Caddyfile',
            str_replace(['VALET_HOME_PATH', 'VALET_SERVER_PATH'], [VALET_HOME_PATH, VALET_SERVER_PATH], $this->files->get(__DIR__.'/../stubs/Caddyfile'))
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

        $this->rewriteSecureCaddyFiles();
    }

    /**
     * Generate fresh Caddyfiles for existing secure sites.
     *
     * This simplifies upgrading when the Caddyfile structure changes.
     *
     * @return void
     */
    function rewriteSecureCaddyFiles()
    {
        $domain = $this->configuration->read()['domain'];

        $this->site->resecureForNewDomain($domain, $domain);
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
            $this->files->get(__DIR__.'/../stubs/daemon.plist')
        );

        $this->files->put(
            $this->daemonPath, str_replace('VALET_HOME_PATH', VALET_HOME_PATH, $contents)
        );
    }

    /**
     * Restart the launch daemon.
     *
     * @return void
     */
    function restart()
    {
        $this->cli->quietly('sudo launchctl unload '.$this->daemonPath);

        $this->cli->quietly('sudo launchctl load '.$this->daemonPath);
    }

    /**
     * Stop the launch daemon.
     *
     * @return void
     */
    function stop()
    {
        $this->cli->quietly('sudo launchctl unload '.$this->daemonPath);
    }

    /**
     * Remove the launch daemon.
     *
     * @return void
     */
    function uninstall()
    {
        $this->stop();

        $this->files->unlink($this->daemonPath);
    }
}
