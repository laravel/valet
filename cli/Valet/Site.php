<?php

namespace Valet;

use DomainException;

class Site
{
    var $config, $cli, $files;

    /**
     * Create a new Site instance.
     *
     * @param  Configuration  $config
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    function __construct(Configuration $config, CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
        $this->config = $config;
    }

    /**
     * Link the current working directory with the given name.
     *
     * @param  string  $target
     * @param  string  $name
     * @return string
     */
    function link($target, $link)
    {
        $this->files->ensureDirExists(
            $linkPath = $this->sitesPath(), user()
        );

        $this->config->prependPath($linkPath);

        $this->files->symlinkAsUser($target, $linkPath.'/'.$link);

        return $linkPath.'/'.$link;
    }

    /**
     * Unlink the given symbolic link.
     *
     * @param  string  $name
     * @return void
     */
    function unlink($name)
    {
        if ($this->files->exists($path = $this->sitesPath().'/'.$name)) {
            $this->files->unlink($path);
        }
    }

    /**
     * Remove all broken symbolic links.
     *
     * @return void
     */
    function pruneLinks()
    {
        if ($this->files->isDir($sitesPath = $this->sitesPath())) {
            $this->files->removeBrokenLinksAt($sitesPath);
        }
    }

    /**
     * Get all of the log files for all sites.
     *
     * @param  array  $paths
     * @return array
     */
    function logs($paths)
    {
        $files = collect();

        foreach ($paths as $path) {
            $files = $files->merge(collect($this->files->scandir($path))->map(function ($directory) use ($path) {
                $logPath = $path.'/'.$directory.'/storage/logs/laravel.log';

                if ($this->files->isDir(dirname($logPath))) {
                    return $this->files->touchAsUser($logPath);
                }
            })->filter());
        }

        return $files->values()->all();
    }

    /**
     * Get the path to the linked Valet sites.
     *
     * @return string
     */
    function sitesPath()
    {
        return VALET_HOME_PATH.'/Sites';
    }
}
