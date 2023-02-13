<?php

namespace Valet;

use Valet\Os\Installer;

abstract class Status
{
    public $debugInstructions = [];

    public function __construct(public Configuration $config, public Installer $installer, public CommandLine $cli, public Filesystem $files)
    {
    }

    abstract public function checks(): array;

    /**
     * Check the status of the entire Valet ecosystem and return a status boolean
     * and a set of individual checks and their respective booleans as well.
     */
    public function check(): array
    {
        $isValid = true;

        $output = collect($this->checks())->map(function (array $check) use (&$isValid) {
            if (! $thisIsValid = $check['check']()) {
                $this->debugInstructions[] = $check['debug'];
                $isValid = false;
            }

            return ['description' => $check['description'], 'success' => $thisIsValid ? 'Yes' : 'No'];
        });

        return [
            'success' => $isValid,
            'output' => $output->all(),
            'debug' => $this->debugInstructions(),
        ];
    }

    public function isValetInstalled(): bool
    {
        return is_dir(VALET_HOME_PATH)
            && file_exists($this->config->path())
            && is_dir(VALET_HOME_PATH.'/Drivers')
            && is_dir(VALET_HOME_PATH.'/Sites')
            && is_dir(VALET_HOME_PATH.'/Log')
            && is_dir(VALET_HOME_PATH.'/Certificates');
    }

    public function debugInstructions(): string
    {
        return collect($this->debugInstructions)->unique()->join(PHP_EOL);
    }
}
