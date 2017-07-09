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
     * @param  Configuration $config
     * @param  CommandLine $cli
     * @param  Filesystem $files
     */
    public function __construct(Configuration $config, CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
        $this->config = $config;
    }

    /**
     * Get the real hostname for the given path, checking links.
     *
     * @param  string $path
     * @return string|null
     */
    public function host($path)
    {
        foreach ($this->files->scandir($this->sitesPath()) as $link) {
            if ($resolved = realpath($this->sitesPath() . '/' . $link) === $path) {
                return $link;
            }
        }

        return basename($path);
    }

    /**
     * Link the current working directory with the given name.
     *
     * @param  string $target
     * @param  string $link
     * @return string
     */
    public function link($target, $link)
    {
        $this->files->ensureDirExists(
            $linkPath = $this->sitesPath(),
            user()
        );

        $this->config->prependPath($linkPath);

        $this->files->symlinkAsUser($target, $linkPath . '/' . $link);

        return $linkPath . '/' . $link;
    }

    /**
     * Pretty print out all links in Valet.
     *
     * @return \Illuminate\Support\Collection
     */
    public function links()
    {
        $certsPath = VALET_HOME_PATH . '/Certificates';

        $this->files->ensureDirExists($certsPath, user());

        $certs = $this->getCertificates($certsPath);

        return $this->getLinks(VALET_HOME_PATH . '/Sites', $certs);
    }

    /**
     * Get all certificates from config folder.
     *
     * @param string $path
     * @return \Illuminate\Support\Collection
     */
    public function getCertificates($path)
    {
        return collect($this->files->scanDir($path))->filter(function ($value, $key) {
            return ends_with($value, '.crt');
        })->map(function ($cert) {
            return substr($cert, 0, -8);
        })->flip();
    }

    /**
     * Get list of links and present them formatted.
     *
     * @param string $path
     * @param \Illuminate\Support\Collection $certs
     * @return \Illuminate\Support\Collection
     */
    public function getLinks($path, $certs)
    {
        $config = $this->config->read();

        return collect($this->files->scanDir($path))->mapWithKeys(function ($site) use ($path) {
            return [$site => $this->files->readLink($path . '/' . $site)];
        })->map(function ($path, $site) use ($certs, $config) {
            $secured = $certs->has($site);
            $url = ($secured ? 'https' : 'http') . '://' . $site . '.' . $config['domain'];

            return [$site, $secured ? ' X' : '', $url, $path];
        });
    }

    /**
     * Unlink the given symbolic link.
     *
     * @param  string $name
     * @return void
     */
    public function unlink($name)
    {
        if ($this->files->exists($path = $this->sitesPath() . '/' . $name)) {
            $this->files->unlink($path);
        }
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
     * Resecure all currently secured sites with a fresh domain.
     *
     * @param  string $oldDomain
     * @param  string $domain
     * @return void
     */
    public function resecureForNewDomain($oldDomain, $domain)
    {
        if (!$this->files->exists($this->certificatesPath())) {
            return;
        }

        $secured = $this->secured();

        foreach ($secured as $url) {
            $this->unsecure($url);
        }

        foreach ($secured as $url) {
            $this->secure(str_replace('.' . $oldDomain, '.' . $domain, $url));
        }
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
     * @param  string $url
     * @return void
     */
    public function secure($url)
    {
        $this->unsecure($url);

        $this->files->ensureDirExists($this->certificatesPath(), user());

        $this->createCertificate($url);

        $this->files->putAsUser(
            VALET_HOME_PATH . '/Nginx/' . $url,
            $this->buildSecureNginxServer($url)
        );
    }

    /**
     * Create and trust a certificate for the given URL.
     *
     * @param  string $url
     * @return void
     */
    public function createCertificate($url)
    {
        $keyPath = $this->certificatesPath() . '/' . $url . '.key';
        $csrPath = $this->certificatesPath() . '/' . $url . '.csr';
        $crtPath = $this->certificatesPath() . '/' . $url . '.crt';
        $confPath = $this->certificatesPath() . '/' . $url . '.conf';

        $this->buildCertificateConf($confPath, $url);
        $this->createPrivateKey($keyPath);
        $this->createSigningRequest($url, $keyPath, $csrPath, $confPath);

        $this->cli->runAsUser(sprintf(
            'openssl x509 -req -sha256 -days 365 -in %s -signkey %s -out %s -extensions v3_req -extfile %s',
            $csrPath, $keyPath, $crtPath, $confPath
        ));

        $this->trustCertificate($crtPath, $url);
    }

    /**
     * Create the private key for the TLS certificate.
     *
     * @param  string $keyPath
     * @return void
     */
    public function createPrivateKey($keyPath)
    {
        $this->cli->runAsUser(sprintf('openssl genrsa -out %s 2048', $keyPath));
    }

    /**
     * Create the signing request for the TLS certificate.
     *
     * @param  string $keyPath
     * @return void
     */
    public function createSigningRequest($url, $keyPath, $csrPath, $confPath)
    {
        $this->cli->runAsUser(sprintf(
            'openssl req -new -key %s -out %s -subj "/C=US/ST=MN/O=Valet/localityName=Valet/commonName=%s/organizationalUnitName=Valet/emailAddress=valet/" -config %s -passin pass:',
            $keyPath, $csrPath, $url, $confPath
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
     * Trust the given certificate file in the Mac Keychain.
     *
     * @param  string $crtPath
     * @return void
     */
    public function trustCertificate($crtPath, $url)
    {
        $this->cli->run(sprintf(
            'certutil -d sql:$HOME/.pki/nssdb -A -t TC -n "%s" -i "%s"', $url, $crtPath
        ));

        $this->cli->run(sprintf(
            'certutil -d $HOME/.mozilla/firefox/*.default -A -t TC -n "%s" -i "%s"', $url, $crtPath
        ));
    }

    /**
     * Build the TLS secured Nginx server for the given URL.
     *
     * @param  string $url
     * @return string
     */
    public function buildSecureNginxServer($url)
    {
        $path = $this->certificatesPath();

        return str_replace(
            ['VALET_HOME_PATH', 'VALET_SERVER_PATH', 'VALET_SITE', 'VALET_CERT', 'VALET_KEY'],
            [VALET_HOME_PATH, VALET_SERVER_PATH, $url, $path . '/' . $url . '.crt', $path . '/' . $url . '.key'],
            $this->files->get(__DIR__ . '/../stubs/secure.valet.conf')
        );
    }

    /**
     * Unsecure the given URL so that it will use HTTP again.
     *
     * @param  string $url
     * @return void
     */
    public function unsecure($url)
    {
        if ($this->files->exists($this->certificatesPath() . '/' . $url . '.crt')) {
            $this->files->unlink(VALET_HOME_PATH . '/Nginx/' . $url);

            $this->files->unlink($this->certificatesPath() . '/' . $url . '.conf');
            $this->files->unlink($this->certificatesPath() . '/' . $url . '.key');
            $this->files->unlink($this->certificatesPath() . '/' . $url . '.csr');
            $this->files->unlink($this->certificatesPath() . '/' . $url . '.crt');

            $this->cli->run(sprintf('certutil -d sql:$HOME/.pki/nssdb -D -n "%s"', $url));
            $this->cli->run(sprintf('certutil -d $HOME/.mozilla/firefox/*.default -D -n "%s"', $url));
        }
    }

    /**
     * Get the path to the linked Valet sites.
     *
     * @return string
     */
    public function sitesPath()
    {
        return VALET_HOME_PATH . '/Sites';
    }

    /**
     * Get the path to the Valet TLS certificates.
     *
     * @return string
     */
    public function certificatesPath()
    {
        return VALET_HOME_PATH . '/Certificates';
    }
}
