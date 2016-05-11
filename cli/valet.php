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
use Valet\Facades\Brew;
use Valet\Facades\Site;
use Valet\Facades\Valet;
use Valet\Facades\Caddy;
use Valet\Facades\Ngrok;
use Valet\Facades\PhpFpm;
use Valet\Facades\DnsMasq;
use Valet\Facades\Filesystem;
use Valet\Facades\CommandLine;
use Valet\Facades\Configuration;
use Illuminate\Container\Container;

/**
 * Create the application.
 */
Container::setInstance(new Container);

$app = new Application('Laravel Valet', 'v1.1.3');

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
    Valet::createSudoersEntries();

    output(PHP_EOL.'<info>Valet installed successfully!</info>');
});

/**
 * Change the domain currently being used by Valet.
 */
$app->command('domain domain', function ($domain) {
    DnsMasq::updateDomain(
        Configuration::read()['domain'], $domain = trim($domain, '.')
    );

    Configuration::updateKey('domain', $domain);

    info('Your Valet domain has been updated to ['.$domain.'].');
});

/**
 * Get the domain currently being used by Valet.
 */
$app->command('current-domain', function () {
    info(Configuration::read()['domain']);
});

/**
 * Add the current working directory to the paths configuration.
 */
$app->command('park', function () {
    Configuration::addPath(getcwd());

    info("This directory has been added to Valet's paths.");
});

/**
 * Remove the current working directory to the paths configuration.
 */
$app->command('forget', function () {
    Configuration::removePath(getcwd());

    info("This directory has been removed from Valet's paths.");
});

/**
 * Register a symbolic link with Valet.
 */
$app->command('link [name]', function ($name) {
    $linkPath = Site::link(getcwd(), $name = $name ?: basename(getcwd()));

    info('A ['.$name.'] symbolic link has been created in ['.$linkPath.'].');
});

/**
 * Display all of the registered symbolic links.
 */
$app->command('links', function () {
    passthru('ls -la '.VALET_HOME_PATH.'/Sites');
});

/**
 * Unlink a link from the Valet links directory.
 */
$app->command('unlink [name]', function ($name) {
    Site::unlink($name ?: basename(getcwd()));

    info('The ['.$name.'] symbolic link has been removed.');
});

/**
 * Determine which Valet driver the current directory is using.
 */
$app->command('which', function () {
    require __DIR__.'/drivers/require.php';

    $driver = ValetDriver::assign(getcwd(), basename(getcwd()), '/');

    if ($driver) {
        info('This site is served by ['.get_class($driver).'].');
    } else {
        output('<fg=red>Valet could not determine which driver to use for this site.</>');
    }
});

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
        output('<fg=red>No log files were found.</>');
    }
});

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
});

/**
 * Echo the currently tunneled URL.
 */
$app->command('fetch-share-url', function () {
    output(Ngrok::currentTunnelUrl());
});

/**
 * Start the daemon services.
 */
$app->command('start', function () {
    PhpFpm::restart();

    Caddy::restart();

    info('Valet services have been started.');
});

/**
 * Restart the daemon services.
 */
$app->command('restart', function () {
    PhpFpm::restart();

    Caddy::restart();

    info('Valet services have been restarted.');
});

/**
 * Stop the daemon services.
 */
$app->command('stop', function () {
    PhpFpm::stop();

    Caddy::stop();

    info('Valet services have been stopped.');
});

/**
 * Uninstall Valet entirely.
 */
$app->command('uninstall', function () {
    Caddy::uninstall();

    info('Valet has been uninstalled.');
});

/**
 * Run the application.
 */
$app->run();
