<?php

namespace Valet;

class Status
{
    public $brewServicesUserOutput;

    public $brewServicesRootOutput;

    public $debugInstructions = [];

    public function __construct(public Configuration $config, public Brew $brew, public CommandLine $cli, public Filesystem $files) {}

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

    /**
     * Define a list of checks to test the health of the Valet ecosystem of tools and configs.
     */
    public function checks(): array
    {
        $linkedPhp = $this->brew->getLinkedPhpFormula();

        return [
            [
                'description' => 'Is Valet fully installed?',
                'check' => function () {
                    return $this->valetInstalled();
                },
                'debug' => 'Run `composer require laravel/valet` and `valet install`.',
            ],
            [
                'description' => 'Is Valet config valid?',
                'check' => function () {
                    try {
                        $config = $this->config->read();

                        foreach (['tld', 'loopback', 'paths'] as $key) {
                            if (! array_key_exists($key, $config)) {
                                $this->debugInstructions[] = 'Your Valet config is missing the "'.$key.'" key. Re-add this manually, or delete your config file and re-install.';

                                return false;
                            }
                        }

                        return true;
                    } catch (\JsonException $e) {
                        return false;
                    }
                },
                'debug' => 'Run `valet install` to update your configuration.',
            ],
            [
                'description' => 'Is Homebrew installed?',
                'check' => function () {
                    return $this->cli->run('which brew') !== '';
                },
                'debug' => 'Visit https://brew.sh/ for instructions on installing Homebrew.',
            ],
            [
                'description' => 'Is DnsMasq installed?',
                'check' => function () {
                    return $this->brew->installed('dnsmasq');
                },
                'debug' => 'Run `valet install`.',
            ],
            [
                'description' => 'Is Dnsmasq running?',
                'check' => function () {
                    return $this->isBrewServiceRunning('dnsmasq');
                },
                'debug' => 'Run `valet restart`.',
            ],
            [
                'description' => 'Is Dnsmasq running as root?',
                'check' => function () {
                    return $this->isBrewServiceRunningAsRoot('dnsmasq');
                },
                'debug' => 'Uninstall Dnsmasq with Brew and run `valet install`.',
            ],
            [
                'description' => 'Is Nginx installed?',
                'check' => function () {
                    return $this->brew->installed('nginx') || $this->brew->installed('nginx-full');
                },
                'debug' => 'Run `valet install`.',
            ],
            [
                'description' => 'Is Nginx running?',
                'check' => function () {
                    return $this->isBrewServiceRunning('nginx');
                },
                'debug' => 'Run `valet restart`.',
            ],
            [
                'description' => 'Is Nginx running as root?',
                'check' => function () {
                    return $this->isBrewServiceRunningAsRoot('nginx');
                },
                'debug' => 'Uninstall nginx with Brew and run `valet install`.',
            ],
            [
                'description' => 'Is PHP installed?',
                'check' => function () {
                    return $this->brew->hasInstalledPhp();
                },
                'debug' => 'Run `valet install`.',
            ],
            [
                'description' => 'Is linked PHP ('.$linkedPhp.') running?',
                'check' => function () use ($linkedPhp) {
                    return $this->isBrewServiceRunning($linkedPhp);
                },
                'debug' => 'Run `valet restart`.',
            ],
            [
                'description' => 'Is linked PHP ('.$linkedPhp.') running as root?',
                'check' => function () use ($linkedPhp) {
                    return $this->isBrewServiceRunningAsRoot($linkedPhp);
                },
                'debug' => 'Uninstall PHP with Brew and run `valet use php@8.2`',
            ],
            [
                'description' => 'Is valet.sock present?',
                'check' => function () {
                    return $this->files->exists(VALET_HOME_PATH.'/valet.sock');
                },
                'debug' => 'Run `valet install`.',
            ],
        ];
    }

    public function isBrewServiceRunning(string $name, bool $exactMatch = true): bool
    {
        return $this->isBrewServiceRunningAsUser($name, $exactMatch)
            || $this->isBrewServiceRunningAsRoot($name, $exactMatch);
    }

    public function isBrewServiceRunningAsRoot(string $name, bool $exactMatch = true): bool
    {
        if (! $this->brewServicesRootOutput) {
            $this->brewServicesRootOutput = $this->jsonFromCli('brew services info --all --json', true);
        }

        return $this->isBrewServiceRunningGivenServiceList($this->brewServicesRootOutput, $name, $exactMatch);
    }

    public function isBrewServiceRunningAsUser(string $name, bool $exactMatch = true): bool
    {
        if (! $this->brewServicesUserOutput) {
            $this->brewServicesUserOutput = $this->jsonFromCli('brew services info --all --json', false);
        }

        return $this->isBrewServiceRunningGivenServiceList($this->brewServicesUserOutput, $name, $exactMatch);
    }

    public function jsonFromCli(string $input, bool $sudo = false): array
    {
        $contents = $sudo ? $this->cli->run($input) : $this->cli->runAsUser($input);
        // Skip to the JSON, to avoid warnings; we're only getting arrays so start with [
        $contents = substr($contents, strpos($contents, '['));

        try {
            return json_decode($contents, false, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $command = $sudo ? 'sudo '.$input : $input;
            throw new \Exception('Invalid JSON returned from command: '.$command);
        }
    }

    protected function isBrewServiceRunningGivenServiceList(array $serviceList, string $name, bool $exactMatch = true): bool
    {
        foreach ($serviceList as $service) {
            if ($service->running === true) {
                if ($exactMatch && $service->name == $name) {
                    return true;
                } elseif (! $exactMatch && str_contains($service->name, $name)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function valetInstalled(): bool
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
