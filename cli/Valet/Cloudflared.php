<?php

namespace Valet;

use GuzzleHttp\Client;

class Cloudflared
{
    public function __construct(public CommandLine $cli, public Brew $brew)
    {
    }

    public function currentTunnelUrl(string $domain): ?string
    {
        return $this->currentCloudflaredTunnels()[$domain] ?? false;
    }

    protected function currentCloudflaredTunnels(): array
    {
        $urls = [];

        // Get all cloudflared processes
        $processes = array_filter(explode("\n", $this->cli->run('pgrep -fl cloudflared')));

        // Every cloudflared process will start a "metrics" web server where the
        // Quick Tunnel URL will be mentioned under the /metrics endpoint
        foreach ($processes as $process) {
            // Get the URL shared in this process
            preg_match('/(?<pid>\d+)\s.+--http-host-header\s(?<domain>[^\s]+).*/', $process, $pgrepMatches);

            if (array_key_exists('domain', $pgrepMatches) && array_key_exists('pid', $pgrepMatches)) {
                // Get the localhost URL (localhost:port) for the metrics server
                $lsof = $this->cli->run("lsof -iTCP -P -a -p {$pgrepMatches['pid']}");
                preg_match('/TCP\s(?<server>[^\s]+:\d+)\s\(LISTEN\)/', $lsof, $lsofMatches);

                if (array_key_exists('server', $lsofMatches)) {
                    try {
                        // Get the shared cloudflared URL from the metrics server output
                        $body = (new Client())->get("http://{$lsofMatches['server']}/metrics")->getBody();
                        preg_match('/userHostname="(?<url>.+)"/', $body->getContents(), $lsofMatches);
                    } catch (\Exception $e) {}

                    $urls[$pgrepMatches['domain']] = $lsofMatches['url'] ?? false;
                }
            }
        }

        return $urls;
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
