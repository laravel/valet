<?php

namespace Valet;

use DomainException;

class Nginx
{
    public $brew;
    public $cli;
    public $files;
    public $configuration;
    public $site;
    const NGINX_CONF = BREW_PREFIX.'/etc/nginx/nginx.conf';

    /**
     * Create a new Nginx instance.
     *
     * @param  Brew  $brew
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @param  Configuration  $configuration
     * @param  Site  $site
     * @return void
     */
    public function __construct(Brew $brew, CommandLine $cli, Filesystem $files,
                         Configuration $configuration, Site $site)
    {
        $this->cli = $cli;
        $this->brew = $brew;
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
        if (! $this->brew->hasInstalledNginx()) {
            $this->brew->installOrFail('nginx', []);
        }

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
        info('Installing nginx configuration...');

        $contents = $this->files->get(__DIR__.'/../stubs/nginx.conf');

        $this->files->putAsUser(
            static::NGINX_CONF,
            str_replace(['VALET_USER', 'VALET_HOME_PATH'], [user(), VALET_HOME_PATH], $contents)
        );
    }

    /**
     * Install the Valet Nginx server configuration file.
     *
     * @return void
     */
    public function installServer()
    {
        $this->files->ensureDirExists(BREW_PREFIX.'/etc/nginx/valet');

        $this->files->putAsUser(
            BREW_PREFIX.'/etc/nginx/valet/valet.conf',
            str_replace(
                ['VALET_HOME_PATH', 'VALET_SERVER_PATH', 'VALET_STATIC_PREFIX'],
                [VALET_HOME_PATH, VALET_SERVER_PATH, VALET_STATIC_PREFIX],
                $this->site->replaceLoopback($this->files->get(__DIR__.'/../stubs/valet.conf'))
            )
        );

        $this->files->putAsUser(
            BREW_PREFIX.'/etc/nginx/fastcgi_params',
            $this->files->get(__DIR__.'/../stubs/fastcgi_params')
        );
    }

    /**
     * Install the Nginx configuration directory to the ~/.config/valet directory.
     *
     * This directory contains all site-specific Nginx servers.
     *
     * @return void
     */
    public function installNginxDirectory()
    {
        info('Installing nginx directory...');

        if (! $this->files->isDir($nginxDirectory = VALET_HOME_PATH.'/Nginx')) {
            $this->files->mkdirAsUser($nginxDirectory);
        }

        $this->files->putAsUser($nginxDirectory.'/.keep', "\n");

        $this->rewriteSecureNginxFiles();
    }

    /**
     * Check nginx.conf for errors.
     */
    private function lint()
    {
        $this->cli->run(
            'sudo nginx -c '.static::NGINX_CONF.' -t',
            function ($exitCode, $outputMessage) {
                throw new DomainException("Nginx cannot start; please check your nginx.conf [$exitCode: $outputMessage].");
            }
        );
    }

    /**
     * Generate fresh Nginx servers for existing secure sites.
     *
     * @return void
     */
    public function rewriteSecureNginxFiles()
    {
        $tld = $this->configuration->read()['tld'];
        $loopback = $this->configuration->read()['loopback'];

        if ($loopback !== VALET_LOOPBACK) {
            $this->site->aliasLoopback(VALET_LOOPBACK, $loopback);
        }

        $config = compact('tld', 'loopback');

        $this->site->resecureForNewConfiguration($config, $config);
    }

    /**
     * Restart the Nginx service.
     *
     * @return void
     */
    public function restart()
    {
        $this->lint();

        $this->brew->restartService($this->brew->nginxServiceName());
    }

    /**
     * Stop the Nginx service.
     *
     * @return void
     */
    public function stop()
    {
        info('Stopping nginx...');

        $this->cli->quietly('sudo brew services stop '.$this->brew->nginxServiceName());
    }

    /**
     * Forcefully uninstall Nginx.
     *
     * @return void
     */
    public function uninstall()
    {
        $this->brew->stopService(['nginx', 'nginx-full']);
        $this->brew->uninstallFormula('nginx nginx-full');
        $this->cli->quietly('rm -rf '.BREW_PREFIX.'/etc/nginx '.BREW_PREFIX.'/var/log/nginx');
    }
}
