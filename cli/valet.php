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

$version = '1.1.17';

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
 * Get or set the domain currently being used by Valet.
 */
$app->command('domain [domain]', function ($domain = null) {
    if ($domain === null) {
        return info(Configuration::read()['domain']);
    }

    DnsMasq::updateDomain(
        $oldDomain = Configuration::read()['domain'], $domain = trim($domain, '.')
    );

    Configuration::updateKey('domain', $domain);

    Site::resecureForNewDomain($oldDomain, $domain);
    PhpFpm::restart();
    Caddy::restart();

    info('Your Valet domain has been updated to ['.$domain.'].');
})->descriptions('Get or set the domain used for Valet sites');

/**
 * Add the current working directory to the paths configuration.
 */
$app->command('park [path]', function ($path = null) {
    $pathToPark = getcwd();
    if ($path !== null) {
        $pathToPark = $path;
    }

    Configuration::addPath($pathToPark);

    info(($path === null ? "This" : "The [{$path}]") . " directory has been added to Valet's paths.");
})->descriptions('Register the current working (or specified) directory with Valet');

/**
 * Remove the current working directory from the paths configuration.
 */
$app->command('forget [path]', function ($path = null) {
    $pathToForget = getcwd();
    if ($path !== null) {
        $pathToForget = $path;
    }

    Configuration::removePath($pathToForget);

    info(($path === null ? "This" : "The [{$path}]") . " directory has been removed from Valet's paths.");
})->descriptions('Remove the current working (or specified) directory from Valet\'s list of paths');

/**
 * Register a symbolic link with Valet.
 */
$app->command('link [name]', function ($name) {
    $linkPath = Site::link(getcwd(), $name = $name ?: basename(getcwd()));

    info('A ['.$name.'] symbolic link has been created in ['.$linkPath.'].');
})->descriptions('Link the current working directory to Valet');

/**
 * Display all of the registered symbolic links.
 */
$app->command('links', function () {
    passthru('ls -la '.VALET_HOME_PATH.'/Sites');
})->descriptions('Display all of the registered Valet links');

/**
 * Unlink a link from the Valet links directory.
 */
$app->command('unlink [name]', function ($name) {
    Site::unlink($name = $name ?: basename(getcwd()));

    info('The ['.$name.'] symbolic link has been removed.');
})->descriptions('Remove the specified Valet link');

/**
 * Secure the given domain with a trusted TLS certificate.
 */
$app->command('secure [domain]', function ($domain = null) {
    $url = ($domain ?: Site::host(getcwd())).'.'.Configuration::read()['domain'];

    Site::secure($url);

    PhpFpm::restart();

    Caddy::restart();

    info('The ['.$url.'] site has been secured with a fresh TLS certificate.');
})->descriptions('Secure the given domain with a trusted TLS certificate');

/**
 * Stop serving the given domain over HTTPS and remove the trusted TLS certificate.
 */
$app->command('unsecure [domain]', function ($domain = null) {
    $url = ($domain ?: Site::host(getcwd())).'.'.Configuration::read()['domain'];

    Site::unsecure($url);

    PhpFpm::restart();

    Caddy::restart();

    info('The ['.$url.'] site will now serve traffic over HTTP.');
})->descriptions('Stop serving the given domain over HTTPS and remove the trusted TLS certificate');

/**
 * Determine which Valet driver the current directory is using.
 */
$app->command('which', function () {
    require __DIR__.'/drivers/require.php';

    $driver = ValetDriver::assign(getcwd(), basename(getcwd()), '/');

    if ($driver) {
        info('This site is served by ['.get_class($driver).'].');
    } else {
        warning('Valet could not determine which driver to use for this site.');
    }
})->descriptions('Determine which Valet driver serves the current working directory');

/**
 * Stream all of the logs for all sites.
 */
$app->command('logs', function () {
    $files = Site::logs(Configuration::read()['paths']);

    $files = collect($files)->transform(function ($file) {
        return escapeshellarg($file);
    })->all();

    if (count($files) > 0) {
        passthru('tail -f '.implode(' ', $files));
    } else {
        warning('No log files were found.');
    }
})->descriptions('Stream all of the logs for all Laravel sites registered with Valet');

/**
 * Display all of the registered paths.
 */
$app->command('paths', function () {
    $paths = Configuration::read()['paths'];

    if (count($paths) > 0) {
        output(json_encode($paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    } else {
        info('No paths have been registered.');
    }
})->descriptions('Get all of the paths registered with Valet');

/**
 * Open the current directory in the browser.
 */
 $app->command('open', function () {
     $url = "http://".Site::host(getcwd()).'.'.Configuration::read()['domain'].'/';

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

    info('Valet services have been started.');
})->descriptions('Start the Valet services');

/**
 * Restart the daemon services.
 */
$app->command('restart', function () {
    PhpFpm::restart();

    Caddy::restart();

    info('Valet services have been restarted.');
})->descriptions('Restart the Valet services');

/**
 * Stop the daemon services.
 */
$app->command('stop', function () {
    PhpFpm::stop();

    Caddy::stop();

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
