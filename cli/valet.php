#!/usr/bin/env php
<?php

/**
 * Load correct autoloader depending on install location.
 */
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/../vendor/autoload.php';
} else {
    require __DIR__.'/../../../autoload.php';
}

use Silly\Application;
use Illuminate\Container\Container;

/**
 * Create the application.
 */
Container::setInstance(new Container);

$version = '1.1.13';

$app = new Application('Laravel Valet', $version);

/**
 * Prune missing directories and symbolic links on every command.
 */
if (is_dir(VALET_HOME_PATH)) {
    Configuration::prune();

    Site::pruneLinks();
}

/**
 * Allow Valet to be run more conveniently by allowing the Node proxy to run password-less sudo.
 */
$app->command('install', function () {
    Caddy::stop();

    Configuration::install();
    Caddy::install();
    PhpFpm::install();
    DnsMasq::install();
    Caddy::restart();
    Valet::symlinkToUsersBin();
    Brew::createSudoersEntry();
    Valet::createSudoersEntry();

    output(PHP_EOL.'<info>Valet installed successfully!</info>');
})->descriptions('Install the Valet services');

/**
 * Manage domains. Add, rename or delete domains.
 */
$app->command('domain [action] [domain] [newDomain]', function ($action, $domain = null, $newDomain = null) {
    if (!in_array($action, ['add', 'rename', 'delete'])) {
        warning(PHP_EOL . sprintf('Invalid action [%s].', $action));
        return info(PHP_EOL . 'Available actions: "add", "rename" or "delete"');
    }

    if ($action != 'add' && ! domain_exists($domain)) {
        return warning(PHP_EOL . 'Domain ['.$domain.'] does not exist.');
    }

    if ($action == 'add') {
        if ($domain == null) {
            return warning(PHP_EOL . 'Specify domain name.');
        }

        DnsMasq::addDomain($domain);
        Configuration::addDomain($domain);

        info('Valet domain ['.$domain.'] has been added.');
    } elseif ($action == 'rename') {
        if ($newDomain === null) {
            return warning(PHP_EOL . 'New domain name not provided.');
        }

        DnsMasq::renameDomain($domain, $newDomain);
        Configuration::renameDomain($domain, $newDomain);
        Site::resecureForNewDomain($domain, $newDomain);

        info('Valet domain ['.$domain.'] has been renamed to ['.$newDomain.'].');
    } elseif ($action == 'delete') {
        DnsMasq::deleteDomain($domain);
        Configuration::deleteDomain($domain);
        Site::unsecureAllForDomain($domain);

        info('Valet domain ['.$domain.'] has been deleted.');
    }

    PhpFpm::restart();
    Caddy::restart();
})->descriptions('Manage Valet domains.', [
    'action' => 'Options: <comment>add</comment>, <comment>rename</comment> or <comment>delete</comment>',
    'domain' => 'Name of domain',
    'newDomain' => 'Only required with <comment>rename</comment> action',
]);

/**
 * Display all domains found in the configuration file.
 */
$app->command('domains', function() {
    $domains = Configuration::getAllDomains();
    if ($domains->isEmpty()) {
        return warning(PHP_EOL.'No domains found.');
    }

    info(PHP_EOL.'[domains]');
    $domains->each(function($domain) {
        output('- '.$domain['domain']);
    });
})->descriptions('Display all available Valet domains');

/**
 * Add the current working directory to the domain's paths in the configuration file.
 */
$app->command('park [domain]', function ($domain = null) {
    if ($domain !== null && ! domain_exists($domain)) {
        return warning(PHP_EOL.'Domain ['.$domain.'] does not exist.');
    }

    if ($domain === null && Configuration::totalDomains() > 1) {
        warning(PHP_EOL.'Please choose which domain you want to park current directory to.');
        output(PHP_EOL.'<comment>Available domains:</comment>');
        return collect(Configuration::getAllDomains())->each(function($domain) {
            output('- '.$domain['domain']);
        });
    }

    $domain = $domain ?: Configuration::getFirstDomain()['domain'];

    Configuration::addPath($domain, getcwd());

    info("This directory has been added to Valet domain's [".$domain."] paths.");
})->descriptions('Register the current working directory with a Valet domain', [
    'domain' => 'Name of domain',
]);

/**
 * Remove the current working directory to the paths configuration.
 */
$app->command('forget [domain]', function ($domain = null) {
    $domains = Configuration::getDomainsByParkedDirectory(getcwd());
    if ($domains->isEmpty()) {
        return warning('Directory is not registered with any Valet domains.');
    }

    if ($domain === null && $domains->count() > 1) {
        warning(PHP_EOL.'Directory is registered with multiple domains.');
        output(PHP_EOL.'<comment>Following domains are registered with directory:</comment>');
        return $domains->each(function($domain) {
            output('- '.$domain['domain']);
        });
    }

    $domain = $domain ?: $domains->first()['domain'];

    Configuration::removePath($domain, getcwd());

    info("This directory has been removed from Valet domain's [".$domain."] paths.");
})->descriptions('Remove the current working directory from a Valet domain\'s list of paths');

/**
 * Register a symbolic link with Valet domain.
 */
$app->command('link [domain] [name]', function ($domain = null, $name = null) {
    if ($domain !== null && ! domain_exists($domain)) {
        return warning(PHP_EOL.'Domain ['.$domain.'] does not exist.');
    }

    if ($domain === null && Configuration::totalDomains() > 1) {
        warning(PHP_EOL.'Please specify which domain you want to register directory with.');
        output(PHP_EOL.'<comment>Available domains:</comment>');
        return collect(Configuration::getAllDomains())->each(function($domain) {
            output('- '.$domain['domain']);
        });
    }

    $domain = $domain ?: Configuration::getFirstDomain()['domain'];

    $linkPath = Site::link($domain, getcwd(), $name = $name ?: basename(getcwd()));

    info('A ['.$name.'] symbolic link has been created in ['.$linkPath.'] for Valet domain ['.$domain.'].');
})->descriptions('Link the current working directory to a Valet domain', [
    'name' => 'Name of symbolic link',
    'domain' => 'Name of domain',
]);

/**
 * Display all of the registered symbolic links.
 */
$app->command('links', function () {
    $linksFound = 0;
    collect(Filesystem::scandir(VALET_HOME_PATH.'/Sites'))->each(function($domain) use (&$linksFound) {
        $domainPath = VALET_HOME_PATH.'/Sites/'.$domain;

        $links = collect(Filesystem::scandir($domainPath));
        if ($links->isEmpty()) {
            return;
        }

        // Add links found to total count
        $linksFound += $links->count();

        info(PHP_EOL.'[' . $domain . ']');
        $links->each(function ($link) use ($domainPath, &$linksFound) {
            output(sprintf('<comment>%s</comment> -> <comment>%s</comment>', $link, Filesystem::readLink($domainPath . '/' . $link)));
        });
    });

    if (!$linksFound) {
        return warning(PHP_EOL.'No links has been registered with any Valet domains.');
    }
})->descriptions('Display all of the registered Valet links');

/**
 * Unlink a link from a registered Valet domain.
 */
$app->command('unlink [domain] [name]', function ($domain = null, $name = null) {
    $name = $name ?: basename(getcwd());

    $domains = Configuration::getDomainsByLinkedDirectory($name);
    if ($domains->isEmpty()) {
        return warning(PHP_EOL.'Directory is not linked with any Valet domains.');
    }

    if ($domain !== null && ! domain_exists($domain)) {
        return warning(PHP_EOL.'Domain ['.$domain.'] does not exist.');
    }

    if ($domain == null && $domains->count() > 1) {
        warning(PHP_EOL.'Directory is linked with multiple domains.');
        output(PHP_EOL.'<comment>Following domains are linked with directory:</comment>');
        return $domains->each(function($domain) {
            output('- '.$domain['domain']);
        });
    }

    $domain = $domain ?: $domains->first()['domain'];

    Site::unlink($domain, $name);

    info('The ['.$name.'] symbolic link has been removed from Valet domain ['.$domain.'].');
})->descriptions('Remove the specified link from a Valet domain', [
    'name' => 'Name of symbolic link',
    'domain' => 'Name of domain',
]);

/**
 * Secure the given domain with a trusted TLS certificate.
 */
$app->command('secure [domain] [host]', function ($domain = null, $host = null) {
    $domains = Configuration::getDomainsByPath(getcwd());
    if ($domains->isEmpty()) {
        return warning(PHP_EOL.'Directory is not registered with any Valet domains.');
    }

    if ($domain !== null && !$domains->contains('domain', $domain)) {
        return warning(PHP_EOL.'Directory is not registered with domain ['.$domain.'].');
    }

    if ($domain == null && count($domains) > 1) {
        warning(PHP_EOL.'Directory is registered with multiple domains.');
        output(PHP_EOL.'<comment>Following domains are registered with directory:</comment>');
        return collect($domains)->each(function($domain) {
            output('- '.$domain['domain']);
        });
    }

    $domain = $domain ?: $domains->first()['domain'];

    $url = ($host ?: Site::host(getcwd())).'.'.$domain;

    Site::secure($url);

    PhpFpm::restart();

    Caddy::restart();

    info('The ['.$url.'] site has been secured with a fresh TLS certificate.');
})->descriptions('Create a TLS certificate for the specified site', [
    'domain' => 'Name of domain to secure',
    'host' => 'Name of host to secure',
]);

/**
 * Unsecure the given domain and delete existing TLS certificates.
 */
$app->command('unsecure [domain] [host]', function ($domain = null, $host = null) {
    $domains = Configuration::getDomainsByPath(getcwd());
    if ($domains->isEmpty()) {
        return warning(PHP_EOL.'Directory is not registered with any Valet domains.');
    }

    if ($domain !== null && ! $domains->contains('domain', $domain)) {
        return warning(PHP_EOL.'Directory is not registered with domain ['.$domain.'].');
    }

    if ($domain == null && count($domains) > 1) {
        warning(PHP_EOL.'Directory is registered with multiple domains.');
        output(PHP_EOL.'<comment>Following domains are registered with directory:</comment>');
        return collect($domains)->each(function($domain) {
            output('- '.$domain['domain']);
        });
    }

    $domain = $domain ?: $domains->first()['domain'];

    $url = ($host ?: Site::host(getcwd())).'.'.$domain;

    Site::unsecure($url);

    PhpFpm::restart();

    Caddy::restart();

    info('The ['.$url.'] site will now serve traffic over HTTP.');
})->descriptions('Remove a TLS certificate from the specified site', [
    'domain' => 'Name of domain to secure',
    'host' => 'Name of host to unsecure'
]);

/**
 * Determine which Valet driver the current directory is using.
 */
$app->command('which', function () {
    require __DIR__.'/drivers/require.php';

    $driver = ValetDriver::assign(getcwd(), basename(getcwd()), '/');

    if ($driver) {
        info(PHP_EOL.'This site is served by ['.get_class($driver).'].');
    } else {
        warning(PHP_EOL.'Valet could not determine which driver to use for this site.');
    }
})->descriptions('Determine which Valet driver serves the current working directory');

/**
 * Stream all of the logs for all sites.
 */
$app->command('logs [domain]', function ($domain = null) {
    if ($domain !== null && domain_exists($domain)) {
        $domains = [Configuration::getDomain($domain)];
    } else {
        $domains = Configuration::getAllDomains();
    }

    $files = Site::logs($domains);

    $files = collect($files)->transform(function ($file) {
        return escapeshellarg($file);
    })->all();

    if (count($files) > 0) {
        passthru('tail -f '.implode(' ', $files));
    } else {
        warning(PHP_EOL.'No log files were found.');
    }
})->descriptions('Stream all of the logs for all Laravel sites registered with a Valet domain');

/**
 * Display all of the registered paths for domain
 */
$app->command('paths [domain]', function ($domain = null) {
    if ($domain !== null && domain_exists($domain)) {
        $domains = [Configuration::getDomain($domain)];
    } else {
        $domains = Configuration::getAllDomains();
    }

    collect($domains)->each(function($domain) {
        $domainPaths = collect($domain['paths']);

        info(PHP_EOL.'[' . $domain['domain'] . ']');

        if (!$domainPaths->isEmpty()) {
             $domainPaths->each(function($path) {
                 output('- '.$path);
             });
        } else {
            output('No paths have been registered');
        }
    });
})->descriptions('Get all of the paths registered with a Valet domain', [
    'domain' => 'Name of domain',
]);

/**
 * Open the current directory in the browser.
 */
 $app->command('open [domain]', function ($domain = null) {
     if (is_null($domain) || ! domain_exists($domain)) {
         $domains = Configuration::getDomainsByPath(getcwd());

         if ($domains->count() > 1) {
             warning(PHP_EOL.'Directory is registered with multiple domains.');
             output(PHP_EOL.'<comment>Following domains are registered with directory:</comment>');
             return collect($domains)->each(function($domain) {
                 output('- '.$domain['domain']);
             });
         } elseif ($domains->isEmpty()) {
             return warning(PHP_EOL.'Directory is not registered with any Valet domains.');
         }

         $domain = $domains->first()['domain'];
     }

     $url = "http://".Site::host(getcwd()).'.'.$domain.'/';

     passthru("open ".escapeshellarg($url));
 })->descriptions('Open the site for the current directory in your browser');

/**
 * Echo the currently tunneled URL.
 */
$app->command('fetch-share-url', function () {
    output(Ngrok::currentTunnelUrl());
})->descriptions('Get the URL to the current Ngrok tunnel');

/**
 * Start the daemon services.
 */
$app->command('start', function () {
    PhpFpm::restart();

    Caddy::restart();

    DnsMasq::restart();

    info('Valet services have been started.');
})->descriptions('Start the Valet services');

/**
 * Restart the daemon services.
 */
$app->command('restart', function () {
    PhpFpm::restart();

    Caddy::restart();

    DnsMasq::restart();

    info('Valet services have been restarted.');
})->descriptions('Restart the Valet services');

/**
 * Stop the daemon services.
 */
$app->command('stop', function () {
    PhpFpm::stop();

    Caddy::stop();

    DnsMasq::restart();

    info('Valet services have been stopped.');
})->descriptions('Stop the Valet services');

/**
 * Uninstall Valet entirely.
 */
$app->command('uninstall', function () {
    Caddy::uninstall();

    info('Valet has been uninstalled.');
})->descriptions('Uninstall the Valet services');

/**
 * Determine if this is the latest release of Valet.
 */
$app->command('on-latest-version', function () use ($version) {
    if (Valet::onLatestVersion($version)) {
        output('YES');
    } else {
        output('NO');
    }
})->descriptions('Determine if this is the latest version of Valet');

/**
 * Load all of the Valet extensions.
 */
foreach (Valet::extensions() as $extension) {
    include $extension;
}

/**
 * Run the application.
 */
$app->run();
