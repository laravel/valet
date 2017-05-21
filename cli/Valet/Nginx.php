<?php

namespace Valet;

use Valet\Contracts\ServiceManager;
use Valet\Contracts\PackageManager;

class Nginx
{
    public $pm;
    public $sm;
    public $cli;
    public $files;
    public $configuration;
    public $site;

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
    public function __construct(PackageManager $pm, ServiceManager $sm, CommandLine $cli, Filesystem $files, Configuration $configuration, Site $site)
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
    public function install()
    {
        $this->pm->ensureInstalled('nginx');
        $this->sm->enable('nginx');
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
    public function installConfiguration()
    {
        $contents = $this->files->get(__DIR__.'/../stubs/nginx.conf');

        $pid_string = 'pid /run/nginx.pid';
        $hasPIDoption = strpos($this->cli->run('cat /lib/systemd/system/nginx.service'), 'pid /');

        if ($hasPIDoption) {
            $pid_string = '# pid /run/nginx.pid';
        }

        $this->files->putAsUser(
            '/etc/nginx/nginx.conf',
            str_replace(['VALET_USER', 'VALET_GROUP', 'VALET_HOME_PATH', 'VALET_PID'], [user(), group(), VALET_HOME_PATH, $pid_string], $contents)
        );
    }

    /**
     * Install the Valet Nginx server configuration file.
     *
     * @return void
     */
    public function installServer()
    {
        $this->files->putAsUser(
            '/etc/nginx/sites-available/valet.conf',
            str_replace(
                ['VALET_HOME_PATH', 'VALET_SERVER_PATH', 'VALET_PORT'],
                [VALET_HOME_PATH, VALET_SERVER_PATH, '80'],
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
    public function installNginxDirectory()
    {
        if (! $this->files->isDir($nginxDirectory = VALET_HOME_PATH.'/Nginx')) {
            $this->files->mkdirAsUser($nginxDirectory);
        }

        $this->files->putAsUser($nginxDirectory.'/.keep', "\n");

        $this->rewriteSecureNginxFiles();
    }

    /**
     * Update the port used by Nginx.
     *
     * @param  string  $newPort
     * @return void
     */
    public function updatePort($newPort)
    {
        $this->files->putAsUser(
            '/etc/nginx/sites-available/valet.conf',
            str_replace(
                ['VALET_HOME_PATH', 'VALET_SERVER_PATH', 'VALET_PORT'],
                [VALET_HOME_PATH, VALET_SERVER_PATH, $newPort],
                $this->files->get(__DIR__.'/../stubs/valet.conf')
            )
        );
    }

    /**
     * Generate fresh Nginx servers for existing secure sites.
     *
     * @return void
     */
    public function rewriteSecureNginxFiles()
    {
        $domain = $this->configuration->read()['domain'];

        $this->site->resecureForNewDomain($domain, $domain);
    }

    /**
     * Restart the Nginx service.
     *
     * @return void
     */
    public function restart()
    {
        $this->sm->restart('nginx');
    }

    /**
     * Stop the Nginx service.
     *
     * @return void
     */
    public function stop()
    {
        $this->sm->stop('nginx');
    }

    /**
     * Nginx service status.
     *
     * @return void
     */
    public function status()
    {
        $this->sm->printStatus('nginx');
    }

    /**
     * Prepare Nginx for uninstallation.
     *
     * @return void
     */
    public function uninstall()
    {
        $this->stop();
    }
}
