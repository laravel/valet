<?php

namespace Valet;

use DomainException;

class Site
{
    public $config;
    public $cli;
    public $files;

    /**
     * Create a new Site instance.
     *
     * @param  Configuration  $config
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     */
    public function __construct(Configuration $config, CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
        $this->config = $config;
    }

    /**
     * Get the name of the site.
     *
     * @param  string|null  $name
     * @return string
     */
    private function getRealSiteName($name)
    {
        if (! is_null($name)) {
            return $name;
        }

        if (is_string($link = $this->getLinkNameByCurrentDir())) {
            return $link;
        }

        return basename(getcwd());
    }

    /**
     * Get link name based on the current directory.
     *
     * @return null|string
     */
    private function getLinkNameByCurrentDir()
    {
        $count = count($links = $this->links()->where('path', getcwd()));

        if ($count == 1) {
            return $links->shift()['site'];
        }

        if ($count > 1) {
            throw new DomainException("There are {$count} links related to the current directory, please specify the name: valet unlink <name>.");
        }
    }

    /**
     * Get the real hostname for the given path, checking links.
     *
     * @param  string  $path
     * @return string|null
     */
    public function host($path)
    {
        foreach ($this->files->scandir($this->sitesPath()) as $link) {
            if ($resolved = realpath($this->sitesPath($link)) === $path) {
                return $link;
            }
        }

        return basename($path);
    }

    /**
     * Link the current working directory with the given name.
     *
     * @param  string  $target
     * @param  string  $link
     * @return string
     */
    public function link($target, $link)
    {
        $this->files->ensureDirExists(
            $linkPath = $this->sitesPath(), user()
        );

        $this->config->prependPath($linkPath);

        $this->files->symlinkAsUser($target, $linkPath.'/'.$link);

        return $linkPath.'/'.$link;
    }

    /**
     * Pretty print out all links in Valet.
     *
     * @return \Illuminate\Support\Collection
     */
    public function links()
    {
        $certsPath = $this->certificatesPath();

        $this->files->ensureDirExists($certsPath, user());

        $certs = $this->getCertificates($certsPath);

        return $this->getSites($this->sitesPath(), $certs);
    }

    /**
     * Pretty print out all parked links in Valet.
     *
     * @return \Illuminate\Support\Collection
     */
    public function parked()
    {
        $certs = $this->getCertificates();

        $links = $this->getSites($this->sitesPath(), $certs);

        $config = $this->config->read();
        $parkedLinks = collect();
        foreach (array_reverse($config['paths']) as $path) {
            if ($path === $this->sitesPath()) {
                continue;
            }

            // Only merge on the parked sites that don't interfere with the linked sites
            $sites = $this->getSites($path, $certs)->filter(function ($site, $key) use ($links) {
                return ! $links->has($key);
            });

            $parkedLinks = $parkedLinks->merge($sites);
        }

        return $parkedLinks;
    }

    /**
     * Get all sites which are proxies (not Links, and contain proxy_pass directive).
     *
     * @return \Illuminate\Support\Collection
     */
    public function proxies()
    {
        $dir = $this->nginxPath();
        $tld = $this->config->read()['tld'];
        $links = $this->links();
        $certs = $this->getCertificates();

        if (! $this->files->exists($dir)) {
            return collect();
        }

        $proxies = collect($this->files->scandir($dir))
        ->filter(function ($site, $key) use ($tld) {
            // keep sites that match our TLD
            return ends_with($site, '.'.$tld);
        })->map(function ($site, $key) use ($tld) {
            // remove the TLD suffix for consistency
            return str_replace('.'.$tld, '', $site);
        })->reject(function ($site, $key) use ($links) {
            return $links->has($site);
        })->mapWithKeys(function ($site) {
            $host = $this->getProxyHostForSite($site) ?: '(other)';

            return [$site => $host];
        })->reject(function ($host, $site) {
            // If proxy host is null, it may be just a normal SSL stub, or something else; either way we exclude it from the list
            return $host === '(other)';
        })->map(function ($host, $site) use ($certs, $tld) {
            $secured = $certs->has($site);
            $url = ($secured ? 'https' : 'http').'://'.$site.'.'.$tld;

            return [
                'site' => $site,
                'secured' => $secured ? ' X' : '',
                'url' => $url,
                'path' => $host,
            ];
        });

        return $proxies;
    }

    /**
     * Determine if the provided site is a valid site, whether parked or linked.
     *
     * @param  string  $valetSite
     * @return bool
     */
    public function isValidSite($valetSite)
    {
        // Remove .tld from sitename if it was provided
        $siteName = str_replace('.'.$this->config->read()['tld'], '', $valetSite);

        return $this->parked()->merge($this->links())->where('site', $siteName)->count() > 0;
    }

    /**
     * Identify whether a site is for a proxy by reading the host name from its config file.
     *
     * @param  string  $site  Site name without TLD
     * @param  string  $configContents  Config file contents
     * @return string|null
     */
    public function getProxyHostForSite($site, $configContents = null)
    {
        $siteConf = $configContents ?: $this->getSiteConfigFileContents($site);

        if (empty($siteConf)) {
            return null;
        }

        $host = null;
        if (preg_match('~proxy_pass\s+(?<host>https?://.*)\s*;~', $siteConf, $patterns)) {
            $host = trim($patterns['host']);
        }

        return $host;
    }

    public function getSiteConfigFileContents($site, $suffix = null)
    {
        $config = $this->config->read();
        $suffix = $suffix ?: '.'.$config['tld'];
        $file = str_replace($suffix, '', $site).$suffix;

        return $this->files->exists($this->nginxPath($file)) ? $this->files->get($this->nginxPath($file)) : null;
    }

    /**
     * Get all certificates from config folder.
     *
     * @param  string  $path
     * @return \Illuminate\Support\Collection
     */
    public function getCertificates($path = null)
    {
        $path = $path ?: $this->certificatesPath();

        $this->files->ensureDirExists($path, user());

        $config = $this->config->read();

        return collect($this->files->scandir($path))->filter(function ($value, $key) {
            return ends_with($value, '.crt');
        })->map(function ($cert) use ($config) {
            $certWithoutSuffix = substr($cert, 0, -4);
            $trimToString = '.';

            // If we have the cert ending in our tld strip that tld specifically
            // if not then just strip the last segment for  backwards compatibility.
            if (ends_with($certWithoutSuffix, $config['tld'])) {
                $trimToString .= $config['tld'];
            }

            return substr($certWithoutSuffix, 0, strrpos($certWithoutSuffix, $trimToString));
        })->flip();
    }

    /**
     * @deprecated Use getSites instead which works for both normal and symlinked paths.
     *
     * @param  string  $path
     * @param  \Illuminate\Support\Collection  $certs
     * @return \Illuminate\Support\Collection
     */
    public function getLinks($path, $certs)
    {
        return $this->getSites($path, $certs);
    }

    /**
     * Get list of sites and return them formatted
     * Will work for symlink and normal site paths.
     *
     * @param $path
     * @param $certs
     * @return \Illuminate\Support\Collection
     */
    public function getSites($path, $certs)
    {
        $config = $this->config->read();

        $this->files->ensureDirExists($path, user());

        return collect($this->files->scandir($path))->mapWithKeys(function ($site) use ($path) {
            $sitePath = $path.'/'.$site;

            if ($this->files->isLink($sitePath)) {
                $realPath = $this->files->readLink($sitePath);
            } else {
                $realPath = $this->files->realpath($sitePath);
            }

            return [$site => $realPath];
        })->filter(function ($path) {
            return $this->files->isDir($path);
        })->map(function ($path, $site) use ($certs, $config) {
            $secured = $certs->has($site);
            $url = ($secured ? 'https' : 'http').'://'.$site.'.'.$config['tld'];

            return [
                'site' => $site,
                'secured' => $secured ? ' X' : '',
                'url' => $url,
                'path' => $path,
            ];
        });
    }

    /**
     * Unlink the given symbolic link.
     *
     * @param  string  $name
     * @return void
     */
    public function unlink($name)
    {
        $name = $this->getRealSiteName($name);

        if ($this->files->exists($path = $this->sitesPath($name))) {
            $this->files->unlink($path);
        }

        return $name;
    }

    /**
     * Remove all broken symbolic links.
     *
     * @return void
     */
    public function pruneLinks()
    {
        $this->files->ensureDirExists($this->sitesPath(), user());

        $this->files->removeBrokenLinksAt($this->sitesPath());
    }

    /**
     * Resecure all currently secured sites with a fresh configuration.
     *
     * There are only two supported values: tld and loopback
     * And those must be submitted in pairs else unexpected results may occur.
     * eg: both $old and $new should contain the same indexes.
     *
     * @param  array  $old
     * @param  array  $new
     * @return void
     */
    public function resecureForNewConfiguration(array $old, array $new)
    {
        if (! $this->files->exists($this->certificatesPath())) {
            return;
        }

        $secured = $this->secured();

        $defaultTld = $this->config->read()['tld'];
        $oldTld = ! empty($old['tld']) ? $old['tld'] : $defaultTld;
        $tld = ! empty($new['tld']) ? $new['tld'] : $defaultTld;

        $defaultLoopback = $this->config->read()['loopback'];
        $oldLoopback = ! empty($old['loopback']) ? $old['loopback'] : $defaultLoopback;
        $loopback = ! empty($new['loopback']) ? $new['loopback'] : $defaultLoopback;

        foreach ($secured as $url) {
            $newUrl = str_replace('.'.$oldTld, '.'.$tld, $url);
            $siteConf = $this->getSiteConfigFileContents($url, '.'.$oldTld);

            if (! empty($siteConf) && strpos($siteConf, '# valet stub: proxy.valet.conf') === 0) {
                // proxy config
                $this->unsecure($url);
                $this->secure(
                    $newUrl,
                    $this->replaceOldLoopbackWithNew(
                        $this->replaceOldDomainWithNew($siteConf, $url, $newUrl),
                        $oldLoopback,
                        $loopback
                    )
                );
            } else {
                // normal config
                $this->unsecure($url);
                $this->secure($newUrl);
            }
        }
    }

    /**
     * Parse Nginx site config file contents to swap old domain to new.
     *
     * @param  string  $siteConf  Nginx site config content
     * @param  string  $old  Old domain
     * @param  string  $new  New domain
     * @return string
     */
    public function replaceOldDomainWithNew($siteConf, $old, $new)
    {
        $lookups = [];
        $lookups[] = '~server_name .*;~';
        $lookups[] = '~error_log .*;~';
        $lookups[] = '~ssl_certificate_key .*;~';
        $lookups[] = '~ssl_certificate .*;~';

        foreach ($lookups as $lookup) {
            preg_match($lookup, $siteConf, $matches);
            foreach ($matches as $match) {
                $replaced = str_replace($old, $new, $match);
                $siteConf = str_replace($match, $replaced, $siteConf);
            }
        }

        return $siteConf;
    }

    /**
     * Parse Nginx site config file contents to swap old loopback address to new.
     *
     * @param  string  $siteConf  Nginx site config content
     * @param  string  $old  Old loopback address
     * @param  string  $new  New loopback address
     * @return string
     */
    public function replaceOldLoopbackWithNew($siteConf, $old, $new)
    {
        $shouldComment = $new === VALET_LOOPBACK;

        $lookups = [];
        $lookups[] = '~#?listen .*:80; # valet loopback~';
        $lookups[] = '~#?listen .*:443 ssl http2; # valet loopback~';
        $lookups[] = '~#?listen .*:60; # valet loopback~';

        foreach ($lookups as $lookup) {
            preg_match($lookup, $siteConf, $matches);
            foreach ($matches as $match) {
                $replaced = str_replace($old, $new, $match);

                if ($shouldComment && strpos($replaced, '#') !== 0) {
                    $replaced = '#'.$replaced;
                }

                if (! $shouldComment) {
                    $replaced = ltrim($replaced, '#');
                }

                $siteConf = str_replace($match, $replaced, $siteConf);
            }
        }

        return $siteConf;
    }

    /**
     * Get all of the URLs that are currently secured.
     *
     * @return array
     */
    public function secured()
    {
        return collect($this->files->scandir($this->certificatesPath()))
                    ->map(function ($file) {
                        return str_replace(['.key', '.csr', '.crt', '.conf'], '', $file);
                    })->unique()->values()->all();
    }

    /**
     * Secure the given host with TLS.
     *
     * @param  string  $url
     * @param  string  $siteConf  pregenerated Nginx config file contents
     * @param  int  $certificateExpireInDays  The number of days the self signed certificate is valid.
     *                                        Certificates SHOULD NOT have a validity period greater than 397 days.
     * @param  int  $caExpireInYears  The number of years the self signed certificate authority is valid.
     *
     * @see https://github.com/cabforum/servercert/blob/main/docs/BR.md
     *
     * @return void
     */
    public function secure($url, $siteConf = null, $certificateExpireInDays = 396, $caExpireInYears = 20)
    {
        // Extract in order to later preserve custom PHP version config when securing
        $phpVersion = $this->customPhpVersion($url);

        $this->unsecure($url);

        $this->files->ensureDirExists($this->caPath(), user());

        $this->files->ensureDirExists($this->certificatesPath(), user());

        $this->files->ensureDirExists($this->nginxPath(), user());

        $caExpireInDate = (new \DateTime())->diff(new \DateTime("+{$caExpireInYears} years"));

        $this->createCa($caExpireInDate->format('%a'));
        $this->createCertificate($url, $certificateExpireInDays);

        $siteConf = $this->buildSecureNginxServer($url, $siteConf);

        // If the user had isolated the PHP version for this site, swap out .sock file
        if ($phpVersion) {
            $siteConf = $this->replaceSockFile($siteConf, "valet{$phpVersion}.sock", $phpVersion);
        }

        $this->files->putAsUser($this->nginxPath($url), $siteConf);
    }

    /**
     * If CA and root certificates are nonexistent, create them and trust the root cert.
     *
     * @param  int  $caExpireInDays  The number of days the self signed certificate authority is valid.
     * @return void
     */
    public function createCa($caExpireInDays)
    {
        $caPemPath = $this->caPath('LaravelValetCASelfSigned.pem');
        $caKeyPath = $this->caPath('LaravelValetCASelfSigned.key');

        if ($this->files->exists($caKeyPath) && $this->files->exists($caPemPath)) {
            return;
        }

        $oName = 'Laravel Valet CA Self Signed Organization';
        $cName = 'Laravel Valet CA Self Signed CN';

        if ($this->files->exists($caKeyPath)) {
            $this->files->unlink($caKeyPath);
        }
        if ($this->files->exists($caPemPath)) {
            $this->files->unlink($caPemPath);
        }

        $this->cli->run(sprintf(
            'sudo security delete-certificate -c "%s" /Library/Keychains/System.keychain',
            $cName
        ));

        $this->cli->runAsUser(sprintf(
            'openssl req -new -newkey rsa:2048 -days %s -nodes -x509 -subj "/C=/ST=/O=%s/localityName=/commonName=%s/organizationalUnitName=Developers/emailAddress=%s/" -keyout "%s" -out "%s"',
            $caExpireInDays, $oName, $cName, 'rootcertificate@laravel.valet', $caKeyPath, $caPemPath
        ));
        $this->trustCa($caPemPath);
    }

    /**
     * Create and trust a certificate for the given URL.
     *
     * @param  string  $url
     * @param  int  $caExpireInDays  The number of days the self signed certificate is valid.
     * @return void
     */
    public function createCertificate($url, $caExpireInDays)
    {
        $caPemPath = $this->caPath('LaravelValetCASelfSigned.pem');
        $caKeyPath = $this->caPath('LaravelValetCASelfSigned.key');
        $caSrlPath = $this->caPath('LaravelValetCASelfSigned.srl');
        $keyPath = $this->certificatesPath($url, 'key');
        $csrPath = $this->certificatesPath($url, 'csr');
        $crtPath = $this->certificatesPath($url, 'crt');
        $confPath = $this->certificatesPath($url, 'conf');

        $this->buildCertificateConf($confPath, $url);
        $this->createPrivateKey($keyPath);
        $this->createSigningRequest($url, $keyPath, $csrPath, $confPath);

        $caSrlParam = '-CAserial "'.$caSrlPath.'"';
        if (! $this->files->exists($caSrlPath)) {
            $caSrlParam .= ' -CAcreateserial';
        }

        $result = $this->cli->runAsUser(sprintf(
            'openssl x509 -req -sha256 -days %s -CA "%s" -CAkey "%s" %s -in "%s" -out "%s" -extensions v3_req -extfile "%s"',
            $caExpireInDays, $caPemPath, $caKeyPath, $caSrlParam, $csrPath, $crtPath, $confPath
        ));

        // If cert could not be created using runAsUser(), use run().
        if (strpos($result, 'Permission denied')) {
            $this->cli->run(sprintf(
                'openssl x509 -req -sha256 -days %s -CA "%s" -CAkey "%s" %s -in "%s" -out "%s" -extensions v3_req -extfile "%s"',
                $caExpireInDays, $caPemPath, $caKeyPath, $caSrlParam, $csrPath, $crtPath, $confPath
            ));
        }

        $this->trustCertificate($crtPath);
    }

    /**
     * Create the private key for the TLS certificate.
     *
     * @param  string  $keyPath
     * @return void
     */
    public function createPrivateKey($keyPath)
    {
        $this->cli->runAsUser(sprintf('openssl genrsa -out "%s" 2048', $keyPath));
    }

    /**
     * Create the signing request for the TLS certificate.
     *
     * @param  string  $keyPath
     * @return void
     */
    public function createSigningRequest($url, $keyPath, $csrPath, $confPath)
    {
        $this->cli->runAsUser(sprintf(
            'openssl req -new -key "%s" -out "%s" -subj "/C=/ST=/O=/localityName=/commonName=%s/organizationalUnitName=/emailAddress=%s%s/" -config "%s"',
            $keyPath, $csrPath, $url, $url, '@laravel.valet', $confPath
        ));
    }

    /**
     * Trust the given root certificate file in the Mac Keychain.
     *
     * @param  string  $pemPath
     * @return void
     */
    public function trustCa($caPemPath)
    {
        $this->cli->run(sprintf(
            'sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain "%s"', $caPemPath
        ));
    }

    /**
     * Trust the given certificate file in the Mac Keychain.
     *
     * @param  string  $crtPath
     * @return void
     */
    public function trustCertificate($crtPath)
    {
        $this->cli->run(sprintf(
            'sudo security add-trusted-cert -d -r trustAsRoot -k /Library/Keychains/System.keychain "%s"', $crtPath
        ));
    }

    /**
     * Build the SSL config for the given URL.
     *
     * @param  string  $url
     * @return string
     */
    public function buildCertificateConf($path, $url)
    {
        $config = str_replace('VALET_DOMAIN', $url, $this->files->get(__DIR__.'/../stubs/openssl.conf'));
        $this->files->putAsUser($path, $config);
    }

    /**
     * Build the TLS secured Nginx server for the given URL.
     *
     * @param  string  $url
     * @param  string  $siteConf  (optional) Nginx site config file content
     * @return string
     */
    public function buildSecureNginxServer($url, $siteConf = null)
    {
        if ($siteConf === null) {
            $siteConf = $this->replaceOldLoopbackWithNew(
                $this->files->get(__DIR__.'/../stubs/secure.valet.conf'),
                'VALET_LOOPBACK',
                $this->valetLoopback()
            );
        }

        return str_replace(
            ['VALET_HOME_PATH', 'VALET_SERVER_PATH', 'VALET_STATIC_PREFIX', 'VALET_SITE', 'VALET_CERT', 'VALET_KEY'],
            [
                $this->valetHomePath(),
                VALET_SERVER_PATH,
                VALET_STATIC_PREFIX,
                $url,
                $this->certificatesPath($url, 'crt'),
                $this->certificatesPath($url, 'key'),
            ],
            $siteConf
        );
    }

    /**
     * Build the Nginx server for the given valet site.
     *
     * @param  string  $valetSite
     * @param  string  $fpmSockName
     * @param $phpVersion
     * @return void
     */
    public function installSiteConfig($valetSite, $fpmSockName, $phpVersion)
    {
        if ($this->files->exists($this->nginxPath($valetSite))) {
            $siteConf = $this->files->get($this->nginxPath($valetSite));
            $siteConf = $this->replaceSockFile($siteConf, $fpmSockName, $phpVersion);
        } else {
            $siteConf = str_replace(
                ['VALET_HOME_PATH', 'VALET_SERVER_PATH', 'VALET_STATIC_PREFIX', 'VALET_SITE', 'VALET_PHP_FPM_SOCKET', 'VALET_ISOLATED_PHP_VERSION'],
                [VALET_HOME_PATH, VALET_SERVER_PATH, VALET_STATIC_PREFIX, $valetSite, $fpmSockName, $phpVersion],
                $this->replaceLoopback($this->files->get(__DIR__.'/../stubs/site.valet.conf'))
            );
        }

        $this->files->putAsUser($this->nginxPath($valetSite), $siteConf);
    }

    /**
     * Remove PHP Version isolation from a specific site.
     *
     * @param  string  $valetSite
     * @return void
     */
    public function removeIsolation($valetSite)
    {
        // When site has SSL certificate, just re-generate the nginx config.
        // It will be using the `valet.sock` by default from now
        if ($this->files->exists($this->certificatesPath($valetSite, 'crt'))) {
            $siteConf = $this->buildSecureNginxServer($valetSite);
            $this->files->putAsUser($this->nginxPath($valetSite), $siteConf);
        } else {
            // When site doesn't have SSL, removing the custom nginx config will remove isolation
            $this->files->unlink($this->nginxPath($valetSite));
        }
    }

    /**
     * Unsecure the given URL so that it will use HTTP again.
     *
     * @param  string  $url
     * @return void
     */
    public function unsecure($url)
    {
        // Extract in order to later preserve custom PHP version config when unsecuring. Example output: "74"
        $phpVersion = $this->customPhpVersion($url);

        if ($this->files->exists($this->certificatesPath($url, 'crt'))) {
            $this->files->unlink($this->nginxPath($url));

            $this->files->unlink($this->certificatesPath($url, 'conf'));
            $this->files->unlink($this->certificatesPath($url, 'key'));
            $this->files->unlink($this->certificatesPath($url, 'csr'));
            $this->files->unlink($this->certificatesPath($url, 'crt'));
        }

        $this->cli->run(sprintf('sudo security delete-certificate -c "%s" /Library/Keychains/System.keychain', $url));
        $this->cli->run(sprintf('sudo security delete-certificate -c "*.%s" /Library/Keychains/System.keychain', $url));
        $this->cli->run(sprintf(
            'sudo security find-certificate -e "%s%s" -a -Z | grep SHA-1 | sudo awk \'{system("security delete-certificate -Z \'$NF\' /Library/Keychains/System.keychain")}\'',
            $url, '@laravel.valet'
        ));

        // If the user had isolated the PHP version for this site, swap out .sock file
        if ($phpVersion) {
            $this->installSiteConfig($url, "valet{$phpVersion}.sock", $phpVersion);
        }
    }

    public function unsecureAll()
    {
        $tld = $this->config->read()['tld'];

        $secured = $this->parked()
            ->merge($this->links())
            ->sort()
            ->where('secured', ' X');

        if ($secured->count() === 0) {
            return info('No sites to unsecure. You may list all servable sites or links by running <comment>valet parked</comment> or <comment>valet links</comment>.');
        }

        info('Attempting to unsecure the following sites:');
        table(['Site', 'SSL', 'URL', 'Path'], $secured->toArray());

        foreach ($secured->pluck('site') as $url) {
            $this->unsecure($url.'.'.$tld);
        }

        $remaining = $this->parked()
            ->merge($this->links())
            ->sort()
            ->where('secured', ' X');
        if ($remaining->count() > 0) {
            warning('We were not succesful in unsecuring the following sites:');
            table(['Site', 'SSL', 'URL', 'Path'], $remaining->toArray());
        }
        info('unsecure --all was successful.');
    }

    /**
     * Build the Nginx proxy config for the specified domain.
     *
     * @param  string  $url  The domain name to serve
     * @param  string  $host  The URL to proxy to, eg: http://127.0.0.1:8080
     * @param  bool  $secure
     * @return string
     */
    public function proxyCreate($url, $host, $secure = false)
    {
        if (! preg_match('~^https?://.*$~', $host)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid URL', $host));
        }

        $tld = $this->config->read()['tld'];
        if (! ends_with($url, '.'.$tld)) {
            $url .= '.'.$tld;
        }

        $siteConf = $this->replaceOldLoopbackWithNew(
            $this->files->get(
                $secure ? __DIR__.'/../stubs/secure.proxy.valet.conf' : __DIR__.'/../stubs/proxy.valet.conf'
            ),
            'VALET_LOOPBACK',
            $this->valetLoopback()
        );

        $siteConf = str_replace(
            ['VALET_HOME_PATH', 'VALET_SERVER_PATH', 'VALET_STATIC_PREFIX', 'VALET_SITE', 'VALET_PROXY_HOST'],
            [$this->valetHomePath(), VALET_SERVER_PATH, VALET_STATIC_PREFIX, $url, $host],
            $siteConf
        );

        if ($secure) {
            $this->secure($url, $siteConf);
        } else {
            $this->put($url, $siteConf);
        }

        $protocol = $secure ? 'https' : 'http';

        info('Valet will now proxy ['.$protocol.'://'.$url.'] traffic to ['.$host.'].');
    }

    /**
     * Unsecure the given URL so that it will use HTTP again.
     *
     * @param  string  $url
     * @return void
     */
    public function proxyDelete($url)
    {
        $tld = $this->config->read()['tld'];
        if (! ends_with($url, '.'.$tld)) {
            $url .= '.'.$tld;
        }

        $this->unsecure($url);
        $this->files->unlink($this->nginxPath($url));

        info('Valet will no longer proxy [https://'.$url.'].');
    }

    /**
     * Create the given nginx host.
     *
     * @param  string  $url
     * @param  string  $siteConf  pregenerated Nginx config file contents
     * @return void
     */
    public function put($url, $siteConf)
    {
        $this->unsecure($url);

        $this->files->ensureDirExists($this->nginxPath(), user());

        $this->files->putAsUser(
            $this->nginxPath($url), $siteConf
        );
    }

    /**
     * Remove old loopback interface alias and add a new one if necessary.
     *
     * @param  string  $oldLoopback
     * @param  string  $loopback
     * @return void
     */
    public function aliasLoopback($oldLoopback, $loopback)
    {
        if ($oldLoopback !== VALET_LOOPBACK) {
            $this->removeLoopbackAlias($oldLoopback);
        }

        if ($loopback !== VALET_LOOPBACK) {
            $this->addLoopbackAlias($loopback);
        }

        $this->updateLoopbackPlist($loopback);
    }

    /**
     * Remove loopback interface alias.
     *
     * @param  string  $loopback
     * @return void
     */
    public function removeLoopbackAlias($loopback)
    {
        $this->cli->run(sprintf(
            'sudo ifconfig lo0 -alias %s', $loopback
        ));

        info('['.$loopback.'] loopback interface alias removed.');
    }

    /**
     * Add loopback interface alias.
     *
     * @param  string  $loopback
     * @return void
     */
    public function addLoopbackAlias($loopback)
    {
        $this->cli->run(sprintf(
            'sudo ifconfig lo0 alias %s', $loopback
        ));

        info('['.$loopback.'] loopback interface alias added.');
    }

    /**
     * Remove old LaunchDaemon and create a new one if necessary.
     *
     * @param  string  $loopback
     * @return void
     */
    public function updateLoopbackPlist($loopback)
    {
        $this->removeLoopbackPlist();

        if ($loopback !== VALET_LOOPBACK) {
            $this->files->put(
                $this->plistPath(),
                str_replace(
                    'VALET_LOOPBACK',
                    $loopback,
                    $this->files->get(__DIR__.'/../stubs/loopback.plist')
                )
            );

            info('['.$this->plistPath().'] persistent loopback interface alias launch daemon added.');
        }
    }

    /**
     * Remove loopback interface alias launch daemon plist file.
     *
     * @return void
     */
    public function removeLoopbackPlist()
    {
        if ($this->files->exists($this->plistPath())) {
            $this->files->unlink($this->plistPath());

            info('['.$this->plistPath().'] persistent loopback interface alias launch daemon removed.');
        }
    }

    /**
     * Remove loopback interface alias and launch daemon plist file for uninstall purpose.
     *
     * @return void
     */
    public function uninstallLoopback()
    {
        if (($loopback = $this->valetLoopback()) !== VALET_LOOPBACK) {
            $this->removeLoopbackAlias($loopback);
        }

        $this->removeLoopbackPlist();
    }

    public function valetHomePath()
    {
        return VALET_HOME_PATH;
    }

    public function valetLoopback()
    {
        return $this->config->read()['loopback'];
    }

    /**
     * Get the path to loopback LaunchDaemon.
     *
     * @return string
     */
    public function plistPath()
    {
        return '/Library/LaunchDaemons/com.laravel.valet.loopback.plist';
    }

    /**
     * Get the path to Nginx site configuration files.
     */
    public function nginxPath($additionalPath = null)
    {
        return $this->valetHomePath().'/Nginx'.($additionalPath ? '/'.$additionalPath : '');
    }

    /**
     * Get the path to the linked Valet sites.
     *
     * @return string
     */
    public function sitesPath($link = null)
    {
        return $this->valetHomePath().'/Sites'.($link ? '/'.$link : '');
    }

    /**
     * Get the path to the Valet CA certificates.
     *
     * @return string
     */
    public function caPath($caFile = null)
    {
        return $this->valetHomePath().'/CA'.($caFile ? '/'.$caFile : '');
    }

    /**
     * Get the path to the Valet TLS certificates.
     *
     * @return string
     */
    public function certificatesPath($url = null, $extension = null)
    {
        $url = $url ? '/'.$url : '';
        $extension = $extension ? '.'.$extension : '';

        return $this->valetHomePath().'/Certificates'.$url.$extension;
    }

    /**
     * Replace Loopback configuration line in Valet site configuration file contents.
     *
     * @param  string  $siteConf
     * @return string
     */
    public function replaceLoopback($siteConf)
    {
        $loopback = $this->config->read()['loopback'];

        if ($loopback === VALET_LOOPBACK) {
            return $siteConf;
        }

        $str = '#listen VALET_LOOPBACK:80; # valet loopback';

        return str_replace(
            $str,
            substr(str_replace('VALET_LOOPBACK', $loopback, $str), 1),
            $siteConf
        );
    }

    /**
     * Extract PHP version of exising nginx conifg.
     *
     * @param  string  $url
     * @return string|void
     */
    public function customPhpVersion($url)
    {
        if ($this->files->exists($this->nginxPath($url))) {
            $siteConf = $this->files->get($this->nginxPath($url));

            if (starts_with($siteConf, '# Valet isolated PHP version')) {
                $firstLine = explode(PHP_EOL, $siteConf)[0];

                return preg_replace("/[^\d]*/", '', $firstLine); // Example output: "74" or "81"
            }
        }
    }

    /**
     * Replace .sock file in an Nginx site configuration file contents.
     *
     * @param  string  $siteConf
     * @param  string  $sockFile
     * @param  string  $phpVersion
     * @return string
     */
    public function replaceSockFile($siteConf, $sockFile, $phpVersion)
    {
        $siteConf = preg_replace('/valet[0-9]*.sock/', $sockFile, $siteConf);

        if (! starts_with($siteConf, '# Valet isolated PHP version')) {
            $siteConf = '# Valet isolated PHP version : '.$phpVersion.PHP_EOL.$siteConf;
        }

        return $siteConf;
    }
}
