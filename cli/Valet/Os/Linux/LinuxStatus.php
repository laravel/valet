<?php

namespace Valet\Os\Linux;

use Valet\Status;

class LinuxStatus extends Status
{
    /**
     * Define a list of checks to test the health of the Valet ecosystem of tools and configs.
     */
    public function checks(): array
    {
        $linkedPhp = $this->installer->getLinkedPhpFormula();

        return [
            [
                'description' => 'Is Valet fully installed?',
                'check' => function () {
                    return $this->isValetInstalled();
                },
                'debug' => 'Run `composer require laravel/valet` and `valet install`.',
            ],
            [
                'description' => 'Is Valet config valid?',
                'check' => function () {
                    try {
                        $config = $this->config->read();

                        foreach (['tld', 'loopback', 'paths'] as $key) {
                            if (!array_key_exists($key, $config)) {
                                $this->debugInstructions[] = 'Your Valet config is missing the "' . $key . '" key. Re-add this manually, or delete your config file and re-install.';

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
                    return $this->installer->installed('dnsmasq');
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
                    return $this->installer->installed('nginx') || $this->installer->installed('nginx-full');
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
                    return $this->installer->hasInstalledPhp();
                },
                'debug' => 'Run `valet install`.',
            ],
            [
                'description' => 'Is linked PHP (' . $linkedPhp . ') running?',
                'check' => function () use ($linkedPhp) {
                    return $this->isBrewServiceRunning($linkedPhp);
                },
                'debug' => 'Run `valet restart`.',
            ],
            [
                'description' => 'Is linked PHP (' . $linkedPhp . ') running as root?',
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
}
