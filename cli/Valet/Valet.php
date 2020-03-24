<?php

namespace Valet;

use Httpful\Request;

class Valet
{
    var $cli, $files, $brew;
    var $valetBin = '/bin/valet';

    /**
     * Create a new Valet instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @param  Brew $brew
     */
    function __construct(CommandLine $cli, Filesystem $files, Brew $brew)
    {
        $this->cli = $cli;
        $this->files = $files;
        $this->brew = $brew;
    }

    /**
     * Symlink the Valet Bash script into the user's local bin.
     *
     * @return void
     */
    function symlinkToUsersBin()
    {
        $prefix = $this->brew->prefix();
        $this->unlinkFromUsersBin();

        $this->cli->runAsUser('ln -s "'.realpath(__DIR__.'/../../valet').'" '.$prefix.$this->valetBin);
    }

    /**
     * Remove the symlink from the user's local bin.
     *
     * @return void
     */
    function unlinkFromUsersBin()
    {
        $prefix = $this->brew->prefix();
        $this->cli->quietlyAsUser('rm '.$prefix.$this->valetBin);
    }

    /**
     * Get the paths to all of the Valet extensions.
     *
     * @return array
     */
    function extensions()
    {
        if (! $this->files->isDir(VALET_HOME_PATH.'/Extensions')) {
            return [];
        }

        return collect($this->files->scandir(VALET_HOME_PATH.'/Extensions'))
                    ->reject(function ($file) {
                        return is_dir($file);
                    })
                    ->map(function ($file) {
                        return VALET_HOME_PATH.'/Extensions/'.$file;
                    })
                    ->values()->all();
    }

    /**
     * Determine if this is the latest version of Valet.
     *
     * @param  string  $currentVersion
     * @return bool
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    function onLatestVersion($currentVersion)
    {
        $response = Request::get('https://api.github.com/repos/laravel/valet/releases/latest')->send();

        return version_compare($currentVersion, trim($response->body->tag_name, 'v'), '>=');
    }

    /**
     * Create the "sudoers.d" entry for running Valet.
     *
     * @return void
     */
    function createSudoersEntry()
    {
        $this->files->ensureDirExists('/etc/sudoers.d');
        $prefix = $this->brew->prefix();


        $this->files->put('/etc/sudoers.d/valet', "Cmnd_Alias VALET = ${prefix}/bin/valet *
%admin ALL=(root) NOPASSWD:SETENV: VALET".PHP_EOL);
    }

    /**
     * Remove the "sudoers.d" entry for running Valet.
     *
     * @return void
     */
    function removeSudoersEntry()
    {
        $this->cli->quietly('rm /etc/sudoers.d/valet');
    }

    /**
     * Run composer global diagnose
     */
    function composerGlobalDiagnose()
    {
        $this->cli->runAsUser('composer global diagnose');
    }

    /**
     * Run composer global update
     */
    function composerGlobalUpdate()
    {
        $this->cli->runAsUser('composer global update');
    }
}
