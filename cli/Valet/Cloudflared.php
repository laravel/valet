<?php

namespace Valet;

use GuzzleHttp\Client;

class Cloudflared
{
    public function __construct(public CommandLine $cli, public Brew $brew) {}

    public function currentTunnelUrl(string $domain): ?string
    {
        // Every cloudflared process will start a "metrics" web server where the
        // Quick Tunnel URL will be mentioned under the /metrics endpoint; there
        // can potentially be more than one process that matches, but the lsof
        // command will show which one is actually functionally running
        foreach (array_filter(explode(PHP_EOL, $this->cli->run('pgrep -fl cloudflared'))) as $process) {
            // Get the URL shared in this process
            preg_match('/(?<pid>\d+)\s.+--http-host-header\s(?<domain>[^\s]+).*/', $process, $pgrepMatches);

            if (! array_key_exists('domain', $pgrepMatches) || ! array_key_exists('pid', $pgrepMatches)) {
                continue;
            }

            if ($pgrepMatches['domain'] !== $domain) {
                continue;
            }

            // Get the localhost URL for the metrics server
            $lsof = $this->cli->run("lsof -iTCP -P -a -p {$pgrepMatches['pid']}");
            preg_match('/TCP\s(?<server>[^\s]+:\d+)\s\(LISTEN\)/', $lsof, $lsofMatches);

            if (! array_key_exists('server', $lsofMatches)) {
                continue;
            }

            try {
                // Get the cloudflared share URL from the metrics server output
                $body = (new Client)->get("http://{$lsofMatches['server']}/metrics")->getBody();
                preg_match('/userHostname="(?<url>.+)"/', $body->getContents(), $userHostnameMatches);
            } catch (\Exception $e) {
                return false;
            }

            if (array_key_exists('url', $userHostnameMatches)) {
                return $userHostnameMatches['url'];
            }
        }

        return false;
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
