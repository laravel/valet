<?php

namespace Valet;

use Httpful\Exception\ConnectionErrorException;
use Httpful\Request;
use DomainException;

class Ngrok
{
    const STARTING_PORT = 4040;

    const TUNNELS_ENDPOINT = 'http://127.0.0.1:{port}/api/tunnels';

    /**
     * Get the current tunnel URL from the Ngrok API.
     *
     * @return array
     */
    function currentTunnelsUrls($domain = null)
    {
        // wait a second for ngrok to start before attempting to find available tunnels
        sleep(1);

        $responses = [];
        $port = self::STARTING_PORT;
        $loop = true;
        while ($loop) {
            $endpoint = str_replace('{port}', $port, self::TUNNELS_ENDPOINT);
            try {
                Request::get($endpoint)->whenError(
                    static function () use (&$loop) {
                    $loop = false;
                })->send();
            } catch (ConnectionErrorException $e) {
                break;
            }
            $responses[] = retry(20, function () use ($endpoint, $domain) {
                $body = Request::get($endpoint)->send()->body;

                if (isset($body->tunnels) && count($body->tunnels) > 0) {
                    return $this->findHttpTunnelUrl($body->tunnels, $domain);
                }
            }, 250);
            $port++;
        }

        if (!empty($responses)) {
            return $responses;
        }

        throw new DomainException("Tunnel not established.");
    }

    /**
     * Find the HTTP tunnel URL from the list of tunnels.
     *
     * @param  array  $tunnels
     * @return string|null
     */
    function findHttpTunnelUrl($tunnels, $domain)
    {
        // If there are active tunnels on the Ngrok instance we will spin through them and
        // find the one responding on HTTP. Each tunnel has an HTTP and a HTTPS address
        // but for local dev purposes we just desire the plain HTTP URL endpoint.
        foreach ($tunnels as $tunnel) {
            if ($tunnel->proto === 'http' && strpos($tunnel->config->addr, $domain) ) {
                return $tunnel->public_url;
            }
        }
    }
}
