<?php

namespace Valet;

use DomainException;

class Site
{
    var $config, $cli, $files;

    /**
     * Create a new Site instance.
     *
     * @param  Configuration  $config
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     */
    function __construct(Configuration $config, CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
        $this->config = $config;
    }

    /**
     * Get the real hostname for the given path, checking links.
     *
     * @param  string  $path
     * @return string|null
     */
    function host($path)
    {
        foreach ($this->files->scandir($this->sitesPath()) as $link) {
            if ($resolved = realpath($this->sitesPath().'/'.$link) === $path) {
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
    function link($target, $link)
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
    function links()
    {
        $certsPath = VALET_HOME_PATH.'/Certificates';

        $this->files->ensureDirExists($certsPath, user());

        $certs = $this->getCertificates($certsPath);

        return $this->getLinks(VALET_HOME_PATH.'/Sites', $certs);
    }

    /**
     * Get all certificates from config folder.
     *
     * @param string $path
     * @return \Illuminate\Support\Collection
     */
    function getCertificates($path)
    {
        return collect($this->files->scandir($path))->filter(function ($value, $key) {
            return ends_with($value, '.crt');
        })->map(function ($cert) {
            return substr($cert, 0, strripos($cert, '.', -5));
        })->flip();
    }

    /**
     * Get list of links and present them formatted.
     *
     * @param string $path
     * @param \Illuminate\Support\Collection $certs
     * @return \Illuminate\Support\Collection
     */
    function getLinks($path, $certs)
    {
        $config = $this->config->read();

        return collect($this->files->scandir($path))->mapWithKeys(function ($site) use ($path) {
            return [$site => $this->files->readLink($path.'/'.$site)];
        })->map(function ($path, $site) use ($certs, $config) {
            $secured = $certs->has($site);
            $url = ($secured ? 'https': 'http').'://'.$site.'.'.$config['domain'];

            return [$site, $secured ? ' X': '', $url, $path];
        });
    }

    /**
     * Unlink the given symbolic link.
     *
     * @param  string  $name
     * @return void
     */
    function unlink($name)
    {
        if ($this->files->exists($path = $this->sitesPath().'/'.$name)) {
            $this->files->unlink($path);
        }
    }

    /**
     * Remove all broken symbolic links.
     *
     * @return void
     */
    function pruneLinks()
    {
        $this->files->ensureDirExists($this->sitesPath(), user());

        $this->files->removeBrokenLinksAt($this->sitesPath());
    }

    /**
     * Resecure all currently secured sites with a fresh domain.
     *
     * @param  string  $oldDomain
     * @param  string  $domain
     * @return void
     */
    function resecureForNewDomain($oldDomain, $domain)
    {
        if (! $this->files->exists($this->certificatesPath())) {
            return;
        }

        $secured = $this->secured();

        foreach ($secured as $url) {
            $this->unsecure($url);
        }

        foreach ($secured as $url) {
            $this->secure(str_replace('.'.$oldDomain, '.'.$domain, $url));
        }
    }

    /**
     * Get all of the URLs that are currently secured.
     *
     * @return array
     */
    function secured()
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
     * @return void
     */
    function secure($url)
    {
        $this->unsecure($url);

        $this->files->ensureDirExists($this->certificatesPath(), user());

        $this->createCa();

        $this->createCertificate($url);

        $this->files->putAsUser(
            VALET_HOME_PATH.'/Nginx/'.$url, $this->buildSecureNginxServer($url)
        );
    }

    /**
     * If CA and root certificates are nonexistent, crete them and trust the root cert.
     *
     * When created CN and O fields receive random affixes. The random part is saved in file so that later certificate can be untrusted.
     *
     * @return void
     */
    function createCa()
    {
        $caPemPath = $this->certificatesPath().'/LaravelValetCASelfSigned.pem';
        $caKeyPath = $this->certificatesPath().'/LaravelValetCASelfSigned.key';
        $caAffixPath = $this->certificatesPath().'/LaravelValetCASelfSigned.affix';

        if ($this->files->exists($caKeyPath) && $this->files->exists($caPemPath) && $this->files->exists($caAffixPath)) {
            return;
        }

        $oName = 'Laravel Valet CA Self Signed Organization';
        $cName = 'Laravel Valet CA Self Signed CN ';
        $affix = '';

        if ($this->files->exists($caKeyPath)) {
            $this->files->unlink($caKeyPath);
        }
        if ($this->files->exists($caPemPath)) {
            $this->files->unlink($caPemPath);
        }
        if ($this->files->exists($caAffixPath)) {
            $affix = $this->files->get($caAffixPath);
            $this->cli->run(sprintf(
                'sudo security delete-certificate -c "%s%s" /Library/Keychains/System.keychain -t',
                $cName, $affix
            ));
            $this->files->unlink($caAffixPath);
        }
        $this->cli->run(sprintf(
            'sudo security delete-certificate -c "%s" /Library/Keychains/System.keychain -t',
            $cName
        ));

        $affix = bin2hex(openssl_random_pseudo_bytes(15));
        $this->files->putAsUser($caAffixPath, $affix);

        $cName .= $affix;

        $this->cli->runAsUser(sprintf(
            'openssl req -new -newkey rsa:2048 -days 730 -nodes -x509 -subj "/C=/ST=/O=%s/localityName=/commonName=%s/organizationalUnitName=Developers/emailAddress=noreply@valet.test/" -keyout %s -out %s',
            $oName, $cName, $caKeyPath, $caPemPath
        ));
        $this->trustCa($caPemPath);
    }

    /**
     * Create and trust a certificate for the given URL.
     *
     * @param  string  $url
     * @return void
     */
    function createCertificate($url)
    {
        $caPemPath = $this->certificatesPath().'/LaravelValetCASelfSigned.pem';
        $caKeyPath = $this->certificatesPath().'/LaravelValetCASelfSigned.key';
        $keyPath = $this->certificatesPath().'/'.$url.'.key';
        $csrPath = $this->certificatesPath().'/'.$url.'.csr';
        $crtPath = $this->certificatesPath().'/'.$url.'.crt';
        $confPath = $this->certificatesPath().'/'.$url.'.conf';

        $this->buildCertificateConf($confPath, $url);
        $this->createPrivateKey($keyPath);
        $this->createSigningRequest($url, $keyPath, $csrPath, $confPath);

        $this->cli->runAsUser(sprintf(
            'openssl x509 -req -sha256 -days 730 -CA %s -CAkey %s -in %s -out %s -extensions v3_req -extfile %s',
            $caPemPath, $caKeyPath, $csrPath, $crtPath, $confPath
        ));

        $this->trustCertificate($crtPath);
    }

    /**
     * Create the private key for the TLS certificate.
     *
     * @param  string  $keyPath
     * @return void
     */
    function createPrivateKey($keyPath)
    {
        $this->cli->runAsUser(sprintf('openssl genrsa -out %s 2048', $keyPath));
    }

    /**
     * Create the signing request for the TLS certificate.
     *
     * @param  string  $keyPath
     * @return void
     */
    function createSigningRequest($url, $keyPath, $csrPath, $confPath)
    {
        $this->cli->runAsUser(sprintf(
            'openssl req -new -key %s -out %s -subj "/C=/ST=/O=/localityName=/commonName=%s/organizationalUnitName=/emailAddress=/" -config %s',
            $keyPath, $csrPath, $url, $confPath
        ));
    }

    /**
     * Trust the given root certificate file in the Mac Keychain.
     *
     * @param  string  $pemPath
     * @return void
     */
    function trustCa($caPemPath)
    {
        $this->cli->run(sprintf(
            'sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain %s', $caPemPath
        ));
    }

    /**
     * Trust the given certificate file in the Mac Keychain.
     *
     * @param  string  $crtPath
     * @return void
     */
    function trustCertificate($crtPath)
    {
        $this->cli->run(sprintf(
            'sudo security add-trusted-cert -d -r trustAsRoot -k /Library/Keychains/System.keychain %s', $crtPath
        ));
    }

    /**
     * Build the SSL config for the given URL.
     *
     * @param  string  $url
     * @return string
     */
    function buildCertificateConf($path, $url)
    {
        $config = str_replace('VALET_DOMAIN', $url, $this->files->get(__DIR__.'/../stubs/openssl.conf'));
        $this->files->putAsUser($path, $config);
    }

    /**
     * Build the TLS secured Nginx server for the given URL.
     *
     * @param  string  $url
     * @return string
     */
    function buildSecureNginxServer($url)
    {
        $path = $this->certificatesPath();

        return str_replace(
            ['VALET_HOME_PATH', 'VALET_SERVER_PATH', 'VALET_STATIC_PREFIX', 'VALET_SITE', 'VALET_CERT', 'VALET_KEY'],
            [VALET_HOME_PATH, VALET_SERVER_PATH, VALET_STATIC_PREFIX, $url, $path.'/'.$url.'.crt', $path.'/'.$url.'.key'],
            $this->files->get(__DIR__.'/../stubs/secure.valet.conf')
        );
    }

    /**
     * Unsecure the given URL so that it will use HTTP again.
     *
     * @param  string  $url
     * @return void
     */
    function unsecure($url)
    {
        if ($this->files->exists($this->certificatesPath().'/'.$url.'.crt')) {
            $this->files->unlink(VALET_HOME_PATH.'/Nginx/'.$url);

            $this->files->unlink($this->certificatesPath().'/'.$url.'.conf');
            $this->files->unlink($this->certificatesPath().'/'.$url.'.key');
            $this->files->unlink($this->certificatesPath().'/'.$url.'.csr');
            $this->files->unlink($this->certificatesPath().'/'.$url.'.crt');

            $this->cli->run(sprintf('sudo security delete-certificate -c "%s" /Library/Keychains/System.keychain -t', $url));
            $this->cli->run(sprintf('sudo security delete-certificate -c "*.%s" /Library/Keychains/System.keychain -t', $url));
        }
    }

    /**
     * Get the path to the linked Valet sites.
     *
     * @return string
     */
    function sitesPath()
    {
        return VALET_HOME_PATH.'/Sites';
    }

    /**
     * Get the path to the Valet TLS certificates.
     *
     * @return string
     */
    function certificatesPath()
    {
        return VALET_HOME_PATH.'/Certificates';
    }
}
