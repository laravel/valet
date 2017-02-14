<?php

namespace Valet;

use Valet\Contracts\ServiceManager;
use Valet\Contracts\PackageManager;

class Nginx
{
    var $pm;
    var $sm;
    var $cli;
    var $files;
    var $configuration;
    var $site;

    /**
     * Create a new Nginx instance.
     *
     * @param  PackageManager  $pm
     * @param  ServiceManager  $sm
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @param  Configuration  $configuration
     * @param  Site  $site
     * @return void
     */
    function __construct(PackageManager $pm, ServiceManager $sm, CommandLine $cli, Filesystem $files, Configuration $configuration, Site $site)
    {
        $this->cli = $cli;
        $this->pm = $pm;
        $this->sm = $sm;
        $this->site = $site;
        $this->files = $files;
        $this->configuration = $configuration;
    }

    /**
     * Install the configuration files for Nginx.
     *
     * @return void
     */
    function install()
    {
        $this->pm->ensureInstalled('nginx');
        $this->files->ensureDirExists('/etc/nginx/sites-available');
        $this->files->ensureDirExists('/etc/nginx/sites-enabled');

        $this->stop();
        $this->installConfiguration();
        $this->installServer();
        $this->installNginxDirectory();
    }

    /**
     * Install the Nginx configuration file.
     *
     * @return void
     */
    function installConfiguration()
    {
        $contents = $this->files->get(__DIR__.'/../stubs/nginx.conf');

        $this->files->putAsUser(
            '/etc/nginx/nginx.conf',
            str_replace(['VALET_USER', 'VALET_HOME_PATH'], [user(), VALET_HOME_PATH], $contents)
        );
    }

    /**
     * Install the Valet Nginx server configuration file.
     *
     * @return void
     */
    function installServer()
    {
        $this->files->putAsUser(
            '/etc/nginx/sites-available/valet.conf',
            str_replace(
                ['VALET_HOME_PATH', 'VALET_SERVER_PATH'],
                [VALET_HOME_PATH, VALET_SERVER_PATH],
                $this->files->get(__DIR__.'/../stubs/valet.conf')
            )
        );

        if ($this->files->exists('/etc/nginx/sites-enabled/default')) {
            $this->cli->run('rm -f /etc/nginx/sites-enabled/default');
        }

        $this->cli->run('ln -snf /etc/nginx/sites-available/valet.conf /etc/nginx/sites-enabled/valet.conf');

        $this->files->putAsUser(
            '/etc/nginx/fastcgi_params',
            $this->files->get(__DIR__.'/../stubs/fastcgi_params')
        );
    }

    /**
     * Install the Nginx configuration directory to the ~/.valet directory.
     *
     * This directory contains all site-specific Nginx servers.
     *
     * @return void
     */
    function installNginxDirectory()
    {
        if (! $this->files->isDir($nginxDirectory = VALET_HOME_PATH.'/Nginx')) {
            $this->files->mkdirAsUser($nginxDirectory);
        }

        $this->files->putAsUser($nginxDirectory.'/.keep', "\n");

        $this->rewriteSecureNginxFiles();
    }

    /**
     * Generate fresh Nginx servers for existing secure sites.
     *
     * @return void
     */
    function rewriteSecureNginxFiles()
    {
        $domain = $this->configuration->read()['domain'];

        $this->site->resecureForNewDomain($domain, $domain);
    }

    /**
     * Restart the Nginx service.
     *
     * @return void
     */
    function restart()
    {
        $this->sm->restart('nginx');
    }

    /**
     * Stop the Nginx service.
     *
     * @return void
     */
    function stop()
    {
        $this->sm->stop('nginx');
    }

    /**
     * Nginx service status.
     *
     * @return void
     */
    function status()
    {
        $this->sm->status('nginx');
    }

    /**
     * Prepare Nginx for uninstallation.
     *
     * @return void
     */
    function uninstall()
    {
        $this->stop();
    }
}
