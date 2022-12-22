<?php

namespace Valet;

use GuzzleHttp\Client;

class Valet
{
    public $valetBin = BREW_PREFIX.'/bin/valet';

    /**
     * Create a new Valet instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     */
    public function __construct(public CommandLine $cli, public Filesystem $files)
    {
    }

    /**
     * Symlink the Valet Bash script into the user's local bin.
     *
     * @return void
     */
    public function symlinkToUsersBin(): void
    {
        $this->unlinkFromUsersBin();

        $this->cli->runAsUser('ln -s "'.realpath(__DIR__.'/../../valet').'" '.$this->valetBin);
    }

    /**
     * Remove the symlink from the user's local bin.
     *
     * @return void
     */
    public function unlinkFromUsersBin(): void
    {
        $this->cli->quietlyAsUser('rm '.$this->valetBin);
    }

    /**
     * Get the paths to all of the Valet extensions.
     *
     * @return array
     */
    public function extensions(): array
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
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function onLatestVersion(string $currentVersion): bool
    {
        $url = 'https://api.github.com/repos/laravel/valet/releases/latest';
        $response = json_decode((new Client())->get($url)->getBody());

        return version_compare($currentVersion, trim($response->tag_name, 'v'), '>=');
    }

    /**
     * Create the "sudoers.d" entry for running Valet.
     *
     * @return void
     */
    public function createSudoersEntry(): void
    {
        $this->files->ensureDirExists('/etc/sudoers.d');

        $this->files->put('/etc/sudoers.d/valet', 'Cmnd_Alias VALET = '.BREW_PREFIX.'/bin/valet *
%admin ALL=(root) NOPASSWD:SETENV: VALET'.PHP_EOL);
    }

    /**
     * Remove the "sudoers.d" entry for running Valet.
     *
     * @return void
     */
    public function removeSudoersEntry(): void
    {
        $this->cli->quietly('rm /etc/sudoers.d/valet');
    }

    /**
     * Run composer global diagnose.
     *
     * @return void
     */
    public function composerGlobalDiagnose(): void
    {
        $this->cli->runAsUser('composer global diagnose');
    }

    /**
     * Run composer global update.
     *
     * @return void
     */
    public function composerGlobalUpdate(): void
    {
        $this->cli->runAsUser('composer global update');
    }

    /**
     * Get the Valet configuration directory path.
     *
     * @param  string  $file  __FILE__ of server.php
     * @return string
     */
    public function homePath(string $file): string
    {
        return posix_getpwuid(fileowner($file))['dir'].'/.config/valet';
    }
}
