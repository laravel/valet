<?php

namespace Valet;

use GuzzleHttp\Client;

class Cloudflared
{
    public function __construct(public CommandLine $cli, public Brew $brew)
    {
    }

    public function currentTunnelUrl(?string $domain = null)
    {
        $urls = [];
        $processes = array_filter(explode("\n", $this->cli->run('pgrep -fl cloudflared')));

        // Every cloudflare process will start a metrics web server
        // where Quick Tunnel URL is mentioned under /metrics endpoint
        foreach ($processes as $process) {
            preg_match('/(?<pid>\d+)\s.+--http-host-header\s(?<domain>[^\s]+).*/', $process, $matches);
            if (array_key_exists('domain', $matches) && array_key_exists('pid', $matches)) {
                $local_domain = $matches['domain'];
                $lsof = $this->cli->run("lsof -iTCP -P -a -p {$matches['pid']}");
                preg_match('/TCP\s(?<server>[^\s]+:\d+)\s\(LISTEN\)/', $lsof, $matches);
                if (array_key_exists('server', $matches)) {
                    try {
                        $body = (new Client())->get("http://{$matches['server']}/metrics")->getBody();
                        preg_match('/userHostname="(?<url>.+)"/', $body->getContents(), $matches);
                    } catch (\Exception $e) {}

                    $urls[$local_domain] = array_key_exists('url', $matches) ? $matches['url'] : false;
                }
            }
        }

        return array_key_exists($domain, $urls) ? $urls[$domain] : false;
    }

    /**
     * Return whether cloudflared is installed.
     */
    public function installed(): bool
    {
        return $this->brew->installed('cloudflared');
    }

    /**
     * Make sure cloudflared is installed.
     */
    public function ensureInstalled(): void
    {
        $this->brew->ensureInstalled('cloudflared');
    }
}
