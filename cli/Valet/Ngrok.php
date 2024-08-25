<?php

namespace Valet;

use DomainException;
use Exception;
use GuzzleHttp\Client;

class Ngrok
{
    public $tunnelsEndpoints = [
        'http://127.0.0.1:4040/api/tunnels',
        'http://127.0.0.1:4041/api/tunnels',
    ];

    public function __construct(public CommandLine $cli, public Brew $brew) {}

    /**
     * Get the current tunnel URL from the Ngrok API.
     */
    public function currentTunnelUrl(string $domain): string
    {
        // wait a second for ngrok to start before attempting to find available tunnels
        sleep(1);

        foreach ($this->tunnelsEndpoints as $endpoint) {
            try {
                $response = retry(20, function () use ($endpoint, $domain) {
                    $body = json_decode((new Client)->get($endpoint)->getBody());

                    if (isset($body->tunnels) && count($body->tunnels) > 0) {
                        if ($tunnelUrl = $this->findHttpTunnelUrl($body->tunnels, $domain)) {
                            return $tunnelUrl;
                        }
                    }

                    throw new DomainException('Failed to retrieve tunnel URL.');
                }, 250);

                if (! empty($response)) {
                    return $response;
                }
            } catch (Exception $e) {
                // Do nothing, suppress the exception to check the other port
            }
        }

        throw new DomainException('There is no Ngrok tunnel established for '.$domain.'.');
    }

    /**
     * Find the HTTP/HTTPS tunnel URL from the list of tunnels.
     */
    public function findHttpTunnelUrl(array $tunnels, string $domain): ?string
    {
        $httpTunnel = null;
        $httpsTunnel = null;

        // If there are active tunnels on the Ngrok instance we will spin through them and
        // find the one responding on HTTP. Each tunnel has an HTTP and a HTTPS address
        // if no HTTP tunnel is found we will return the HTTPS tunnel as a fallback.

        // Iterate through tunnels to find both HTTP and HTTPS tunnels
        foreach ($tunnels as $tunnel) {
            if (stripos($tunnel->config->addr, $domain)) {
                if ($tunnel->proto === 'http') {
                    $httpTunnel = $tunnel->public_url;
                } elseif ($tunnel->proto === 'https') {
                    $httpsTunnel = $tunnel->public_url;
                }
            }
        }

        // Return HTTP tunnel if available; HTTPS tunnel if not; null if neither
        return $httpTunnel ?? $httpsTunnel;
    }

    /**
     * Set the Ngrok auth token.
     */
    public function setToken($token): string
    {
        return $this->cli->runAsUser(BREW_PREFIX.'/bin/ngrok authtoken '.$token);
    }

    /**
     * Return whether ngrok is installed.
     */
    public function installed(): bool
    {
        return $this->brew->installed('ngrok');
    }

    /**
     * Make sure ngrok is installed.
     */
    public function ensureInstalled(): void
    {
        $this->brew->ensureInstalled('ngrok');
    }
}
