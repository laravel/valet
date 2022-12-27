<?php

namespace Valet;

class Status
{
    /**
     * Create a new Status isntance.
     *
     * @param  Filesystem  $files
     * @param  Brew  $brew
     */
    public function __construct(public Configuration $config, public Brew $brew, public CommandLine $cli, public Filesystem $files)
    {
    }

    /**
     * Check the status of the entire Valet ecosystem and return a status boolean
     * and a set of individual checks and their respective booleans as well.
     *
     * @return array
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
     *
     * @return array
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
                'description' => 'Is Nginx installed?',
                'check' => function () {
                    return $this->brew->installed('nginx');
                },
            ],
            [
                'description' => 'Is PHP installed?',
                'check' => function () {
                    return $this->brew->hasInstalledPhp();
                },
            ],
            [
                'description' => 'Is valet.sock present?',
                'check' => function () {
                    return $this->files->exists(VALET_HOME_PATH.'/valet.sock');
                },
            ],

            // @todo: Are all services (Nginx, Dnsmasq, etc.) running via Brew
            //   .. I ran `brew services list` on my local machine on which Valet is running fine,
            //   and dnsmasq shows a status of "none", as does "nginx", and I wouldnt' know how to
            //   check here which PHP version is the valid one. 😬
            // @todo: Are all configuration items non-erroring (e.g. check the Nginx config, etc.)
            //   .. I ran `nginx -t` on my local machine on which Valet is running fine,
            //   and I got a warning and an emergency 🤣 I give up
        ];
    }
}
