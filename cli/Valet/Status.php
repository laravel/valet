<?php

namespace Valet;

class Status
{
    public $brewServicesUserOutput;
    public $brewServicesSudoOutput;

    public function __construct(public Configuration $config, public Brew $brew, public CommandLine $cli, public Filesystem $files)
    {
    }

    /**
     * Check the status of the entire Valet ecosystem and return a status boolean
     * and a set of individual checks and their respective booleans as well.
     */
    public function check(): array
    {
        $isValid = true;

        $output = collect($this->checks())->map(function (array $check) use (&$isValid) {
            if (! $thisIsValid = $check['check']()) {
                $isValid = false;
            }

            return ['description' => $check['description'], 'success' => $thisIsValid ? 'True' : 'False'];
        });

        return [
            'success' => $isValid,
            'output' => $output->all(),
        ];
    }

    /**
     * Define a list of checks to test the health of the Valet ecosystem of tools and configs.
     */
    public function checks(): array
    {
        return [
            [
                'description' => 'Is Valet installed?',
                'check' => function () {
                    return is_dir(VALET_HOME_PATH) && file_exists($this->config->path());
                },
            ],
            [
                'description' => 'Is Valet config valid?',
                'check' => function () {
                    try {
                        $this->config->read();

                        return true;
                    } catch (\JsonException $e) {
                        return false;
                    }
                },
            ],
            [
                'description' => 'Is Homebrew installed?',
                'check' => function () {
                    return $this->cli->run('which brew') !== '';
                },
            ],
            [
                'description' => 'Is DnsMasq installed?',
                'check' => function () {
                    return $this->brew->installed('dnsmasq');
                },
            ],
            [
                'description' => 'Is Dnsmasq running?',
                'check' => function () {
                    return $this->isBrewServiceRunning('dnsmasq');
                },
            ],
            [
                'description' => 'Is Nginx installed?',
                'check' => function () {
                    return $this->brew->installed('nginx');
                },
            ],
            [
                'description' => 'Is Nginx running?',
                'check' => function () {
                    return $this->isBrewServiceRunning('nginx');
                },
            ],
            [
                'description' => 'Is PHP installed?',
                'check' => function () {
                    return $this->brew->hasInstalledPhp();
                },
            ],
            [
                'description' => 'Is PHP running?',
                'check' => function () {
                    return $this->isBrewServiceRunning('php', exactMatch: false);
                },
            ],
            [
                'description' => 'Is valet.sock present?',
                'check' => function () {
                    return $this->files->exists(VALET_HOME_PATH.'/valet.sock');
                },
            ],
        ];
    }

    public function isBrewServiceRunning(string $name, bool $exactMatch = true): bool
    {
        if (! $this->brewServicesUserOutput) {
            $this->brewServicesUserOutput = json_decode($this->cli->runAsUser('brew services info --all --json'), false);
        }

        if (! $this->brewServicesSudoOutput) {
            $this->brewServicesSudoOutput = json_decode($this->cli->run('brew services info --all --json'), false);
        }

        foreach ([$this->brewServicesUserOutput, $this->brewServicesSudoOutput] as $output) {
            foreach ($output as $service) {
                if ($service->running === true) {
                    if ($exactMatch && $service->name == $name) {
                        return true;
                    } elseif (! $exactMatch && str_contains($service->name, $name)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
