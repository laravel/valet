<?php

namespace Valet;

use Exception;
use DomainException;
use GuzzleHttp\Client;

class Ngrok
{
    public $tunnelsEndpoints = [
        'http://127.0.0.1:4040/api/tunnels',
        'http://127.0.0.1:4041/api/tunnels',
    ];

    /**
     * Get the current tunnel URL from the Ngrok API.
     *
     * @return string
     */
    public function currentTunnelUrl($domain = null)
    {
        // wait a second for ngrok to start before attempting to find available tunnels
        sleep(1);

        foreach ($this->tunnelsEndpoints as $endpoint) {
            try {
                $response = retry(20, function () use ($endpoint, $domain) {
                    $body = json_decode((new Client())->get($endpoint)->getBody());

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

        throw new DomainException('Tunnel not established.');
    }

    /**
     * Find the HTTP tunnel URL from the list of tunnels.
     *
     * @param  array  $tunnels
     * @return string|null
     */
    public function findHttpTunnelUrl($tunnels, $domain)
    {
        // If there are active tunnels on the Ngrok instance we will spin through them and
        // find the one responding on HTTP. Each tunnel has an HTTP and a HTTPS address
        // but for local dev purposes we just desire the plain HTTP URL endpoint.
        foreach ($tunnels as $tunnel) {
            if ($tunnel->proto === 'http' && strpos($tunnel->config->addr, strtolower($domain))) {
                return $tunnel->public_url;
            }
        }
    }
}
