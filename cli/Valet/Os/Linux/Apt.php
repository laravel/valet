<?php

namespace Valet\Os\Mac;

use Valet\CommandLine;
use Valet\Filesystem;

class Apt extends Installer
{
    /**
     * Create a new Apt instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     */
    public function __construct(public CommandLine $cli, public Filesystem $files)
    {
    }


}
