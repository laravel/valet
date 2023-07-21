<?php

namespace Valet;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

class Expose
{
    public function __construct(public Composer $composer, public CommandLine $cli)
    {
    }

    public function currentTunnelUrl(string $domain = null): ?string
    {
        $endpoint = 'http://127.0.0.1:4040/api/tunnels';

        try {
            $response = retry(20, function () use ($endpoint, $domain) {
                $body = json_decode((new Client())->get($endpoint)->getBody());

                if (isset($body->tunnels) && count($body->tunnels) > 0) {
                    if ($tunnelUrl = $this->findHttpTunnelUrl($body->tunnels, $domain)) {
                        return $tunnelUrl;
                    }
                }
            }, 250);

            if (! empty($response)) {
                return $response;
            }

            return warning("The project $domain cannot be found as an Expose share.\nEither it is not currently shared, or you may be on a free plan.");
        } catch (ConnectException $e) {
            return warning('There is no Expose instance running.');
        }
    }

    /**
     * Find the HTTP tunnel URL from the list of tunnels.
     */
    public function findHttpTunnelUrl(array $tunnels, string $domain): ?string
    {
        foreach ($tunnels as $tunnel) {
            if (strpos($tunnel, strtolower($domain))) {
                return $tunnel;
            }
        }

        return null;
    }

    /**
     * Return whether Expose is installed.
     */
    public function installed(): bool
    {
        return $this->composer->installed('beyondcode/expose');
    }

    /**
     * Return which version of Expose is installed.
     */
    public function installedVersion(): ?string
    {
        return $this->composer->installedVersion('beyondcode/expose');
    }

    /**
     * Make sure Expose is installed.
     */
    public function ensureInstalled(): void
    {
        if (! $this->installed()) {
            $this->composer->installOrFail('beyondcode/expose');
        }
    }
}
