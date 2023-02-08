<?php

namespace Valet;

class Server
{
    // Skip constructor promotion until we stop supporting PHP@7.4 isolation
    public $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Extract $uri from $SERVER['REQUEST_URI'] variable.
     */
    public static function uriFromRequestUri(string $requestUri): string
    {
        return rawurldecode(
            explode('?', $requestUri)[0]
        );
    }

    /**
     * Extract the domain from the site name.
     */
    public static function domainFromSiteName(string $siteName): string
    {
        return array_slice(explode('.', $siteName), -1)[0];
    }

    /**
     * Show the Valet 404 "Not Found" page.
     */
    public static function show404()
    {
        http_response_code(404);
        require __DIR__.'/../../cli/templates/404.html';
        exit;
    }

    /**
     * Show directory listing or 404 if directory doesn't exist.
     */
    public static function showDirectoryListing(string $valetSitePath, string $uri)
    {
        $is_root = ($uri == '/');
        $directory = ($is_root) ? $valetSitePath : $valetSitePath.$uri;

        if (! file_exists($directory)) {
            static::show404();
        }

        // Sort directories at the top
        $paths = glob("$directory/*");
        usort($paths, function ($a, $b) {
            return (is_dir($a) == is_dir($b)) ? strnatcasecmp($a, $b) : (is_dir($a) ? -1 : 1);
        });

        // Output the HTML for the directory listing
        echo "<h1>Index of $uri</h1>";
        echo '<hr>';
        echo implode('<br>'.PHP_EOL, array_map(function ($path) use ($uri, $is_root) {
            $file = basename($path);

            return ($is_root) ? "<a href='/$file'>/$file</a>" : "<a href='$uri/$file'>$uri/$file/</a>";
        }, $paths));

        exit;
    }

    /**
     * Return whether a given host (from $_SERVER['HTTP_HOST']) is an IP address.
     */
    public static function hostIsIpAddress(string $host): bool
    {
        return preg_match('/^([0-9]+\.){3}[0-9]+$/', $host);
    }

    /**
     * Return the root level Valet site if given the request URI ($_SERVER['REQUEST_URI'])
     * of an address using IP address local access.
     *
     * E.g. URL is 192.168.1.100/onramp.tes/auth/login, passes $uri as onramp.test/auth/login and
     * $tld as 'test', and this method returns onramp.test
     *
     * For use when accessing Valet sites across a local network.
     */
    public static function valetSiteFromIpAddressUri(string $uri, string $tld): ?string
    {
        if (preg_match('/^[-.0-9a-zA-Z]+\.'.$tld.'/', $uri, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Extract site name from HTTP host, stripping www. and supporting wildcard DNS.
     */
    public function siteNameFromHttpHost(string $httpHost): string
    {
        $siteName = basename(
            // Filter host to support wildcard dns feature
            $this->allowWildcardDnsDomains($httpHost),
            '.'.$this->config['tld']
        );

        if (strpos($siteName, 'www.') === 0) {
            $siteName = substr($siteName, 4);
        }

        return $siteName;
    }

    /**
     * You may use wildcard DNS provider nip.io as a tool for testing your site via an IP address.
     * First, determine the IP address of your local computer (like 192.168.0.10).
     * Then, visit http://project.your-ip.nip.io - e.g.: http://laravel.192.168.0.10.nip.io.
     */
    public function allowWildcardDnsDomains(string $domain): string
    {
        $services = [
            '.*.*.*.*.nip.io',
            '-*-*-*-*.nip.io',
        ];

        if (isset($this->config['tunnel_services'])) {
            $services = array_merge($services, (array) $this->config['tunnel_services']);
        }

        $patterns = [];
        foreach ($services as $service) {
            $pattern = preg_quote($service, '#');
            $pattern = str_replace('\*', '.*', $pattern);
            $patterns[] = '(.*)'.$pattern;
        }

        $pattern = implode('|', $patterns);

        if (preg_match('#(?:'.$pattern.')\z#u', $domain, $matches)) {
            $domain = array_pop($matches);
        }

        if (strpos($domain, ':') !== false) {
            $domain = explode(':', $domain)[0];
        }

        return $domain;
    }

    /**
     * Determine the fully qualified path to the site.
     * Inspects registered path directories, case-sensitive.
     */
    public function sitePath(string $siteName): ?string
    {
        $valetSitePath = null;
        $domain = static::domainFromSiteName($siteName);

        foreach ($this->config['paths'] as $path) {
            $handle = opendir($path);

            if ($handle === false) {
                continue;
            }

            $dirs = [];

            while (false !== ($file = readdir($handle))) {
                if (is_dir($path.'/'.$file) && ! in_array($file, ['.', '..'])) {
                    $dirs[] = $file;
                }
            }

            closedir($handle);

            // Note: strtolower used below because Nginx only tells us lowercase names
            foreach ($dirs as $dir) {
                if (strtolower($dir) === $siteName) {
                    // early return when exact match for linked subdomain
                    return $path.'/'.$dir;
                }

                if (strtolower($dir) === $domain) {
                    // no early return here because the foreach may still have some subdomains to process with higher priority
                    $valetSitePath = $path.'/'.$dir;
                }
            }

            if ($valetSitePath) {
                return $valetSitePath;
            }
        }

        return null;
    }

    /**
     * Return the default site path for uncaught URLs, if it's set.
     **/
    public function defaultSitePath(): ?string
    {
        if (isset($this->config['default']) && is_string($this->config['default']) && is_dir($this->config['default'])) {
            return $this->config['default'];
        }

        return null;
    }
}
