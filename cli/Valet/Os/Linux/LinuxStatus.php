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
                'description' => 'Is DnsMasq installed?',
                'check' => function () {
                    return $this->installer->installed('dnsmasq');
                },
                'debug' => 'Run `valet install`.',
            ],
            [
                'description' => 'Is Dnsmasq running?',
                'check' => function () {
                    return $this->isServiceRunning('dnsmasq');
                },
                'debug' => 'Run `valet restart`.',
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
                    return $this->isServiceRunning('nginx');
                },
                'debug' => 'Run `valet restart`.',
            ],
            [
                'description' => 'Is PHP installed?',
                'check' => function () {
                    return $this->installer->hasInstalledPhp();
                },
                'debug' => 'Run `valet install`.',
            ],
            [
                'description' => 'Is linked PHP ('.$linkedPhp.') running?',
                'check' => function () use ($linkedPhp) {
                    return $this->isServiceRunning($linkedPhp);
                },
                'debug' => 'Run `valet restart`.',
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

    public function isServiceRunning(string $service): bool
    {
        $result = $this->cli->run("service $service status");

        return $this->getServiceActiveLineValue($result) === 'active';
    }

    // @todo: There is most likely a much more elegant refactoring opportunity for this
    //        ... probably using regex
    public function getServiceActiveLineValue(string $output): ?string
    {
        if (! str_contains($output, 'Active: ')) {
            return null;
        }

        foreach (preg_split("/\r\n|\n|\r/", $output) as $line) {
            if (strpos($line, 'Active: ') !== false) {
                $fullLine = $line;
                break;
            }
        }

        if (! isset($fullLine)) {
            return null;
        }

        $value = substr($fullLine, strpos($fullLine, 'Active: ') + strlen('Active: '));
        $value = substr($value, 0, strpos($value, ' '));

        return $value;
    }
}
