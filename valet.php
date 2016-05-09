#!/usr/bin/env php
<?php

/**
 * Load correct autoloader depending on install location.
 */
if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require __DIR__.'/vendor/autoload.php';
} else {
    require __DIR__.'/../../autoload.php';
}

use Silly\Application;
use Valet\Facades\Brew;
use Valet\Facades\Site;
use Valet\Facades\Caddy;
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

$app = new Application('Laravel Valet', 'v1.1.0');

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
    should_be_sudo();

    Caddy::stop();

    Configuration::install();

    Caddy::install();

    PhpFpm::install();

    DnsMasq::install();

    Caddy::restart();

    output(PHP_EOL.'<info>Valet installed successfully!</info>');
});

/**
 * Change the domain currently being used by Valet.
 */
$app->command('domain domain', function ($domain) {
    should_be_sudo();

    $domain = trim($domain, '.');

    DnsMasq::updateDomain(Configuration::read()['domain'], $domain);

    Configuration::updateKey('domain', $domain);

    output('<info>Your Valet domain has been updated to ['.$domain.'].</info>');
});

/**
 * Get the domain currently being used by Valet.
 */
$app->command('current-domain', function () {
    output(Configuration::read()['domain']);
});

/**
 * Add the current working directory to the paths configuration.
 */
$app->command('park', function () {
    Configuration::addPath(getcwd());

    output("<info>This directory has been added to Valet's paths.</info>");
});

/**
 * Remove the current working directory to the paths configuration.
 */
$app->command('forget', function () {
    Configuration::removePath(getcwd());

    output("<info>This directory has been removed from Valet's paths.</info>");
});

/**
 * Register a symbolic link with Valet.
 */
$app->command('link [name]', function ($name) {
    $name = $name ?: basename(getcwd());

    $linkPath = Site::link(getcwd(), $name);

    output('<info>A ['.$name.'] symbolic link has been created in ['.$linkPath.'].</info>');
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
    $name = $name ?: basename(getcwd());

    Site::unlink($name);

    output('<info>The ['.$name.'] symbolic link has been removed.</info>');
});

/**
 * Determine which Valet driver the current directory is using.
 */
$app->command('which', function () {
    require __DIR__.'/drivers/require.php';

    $driver = ValetDriver::assign(getcwd(), basename(getcwd()), '/');

    if ($driver) {
        output('<info>This site is served by ['.get_class($driver).'].</info>');
    } else {
        output('<fg=red>Valet could not determine which driver to use for this site.</>');
    }
});

/**
 * Stream all of the logs for all sites.
 */
$app->command('logs', function () {
    $files = Site::logs(Configuration::read()['paths']);

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
        output('No paths have been registered.');
    }
});

/**
 * Echo the currently tunneled URL.
 */
$app->command('fetch-share-url', function () {
    retry(20, function () {
        $response = Httpful\Request::get('http://127.0.0.1:4040/api/tunnels')->send();

        $body = $response->body;

        if (isset($body->tunnels) && count($body->tunnels) > 0) {
            foreach ($body->tunnels as $tunnel) {
                if ($tunnel->proto == 'http') {
                    return $output->write($tunnel->public_url);
                }
            }
        }

        throw new Exception("Tunnel not established.");
    }, 250);
});

/**
 * Start the daemon services.
 */
$app->command('start', function () {
    should_be_sudo();

    PhpFpm::restart();
    Caddy::restart();

    output('<info>Valet services have been started.</info>');
});

/**
 * Restart the daemon services.
 */
$app->command('restart', function () {
    should_be_sudo();

    PhpFpm::restart();
    Caddy::restart();

    output('<info>Valet services have been restarted.</info>');
});

/**
 * Stop the daemon services.
 */
$app->command('stop', function () {
    should_be_sudo();

    PhpFpm::stop();
    Caddy::stop();

    output('<info>Valet services have been stopped.</info>');
});

/**
 * Uninstall Valet entirely.
 */
$app->command('uninstall', function () {
    should_be_sudo();

    Caddy::uninstall();

    output('<info>Valet has been uninstalled.</info>');
});

/**
 * Run the application.
 */
$app->run();
