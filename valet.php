#!/usr/bin/env php
<?php

if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require __DIR__.'/vendor/autoload.php';
} else {
    require __DIR__.'/../../autoload.php';
}

should_be_compatible();

use Silly\Application;

/**
 * Create the application.
 */
$app = new Application('Laravel Valet', 'v0.1.7');

/**
 * Allow Valet to be run more conveniently by allowing the Node proxy to run password-less sudo.
 */
$app->command('install', function ($output) {
    should_be_sudo();

    Valet\LaunchDaemon::install();

    Valet\Configuration::install();

    Valet\DnsMasq::install($output);

    Valet\LaunchDaemon::restart();

    $output->writeln(PHP_EOL.'<info>Valet installed successfully!</info>');
});

/**
 * Add the current working directory to the paths configuration.
 */
$app->command('serve', function ($output) {
    Valet\Configuration::addPath(getcwd());

    $output->writeln("<info>This directory has been added to Valet's served paths.</info>");
});

/**
 * Add the current working directory to the paths configuration.
 */
$app->command('link name', function ($name, $output) {
    $linkPath = Valet\Site::link($name);

    $output->writeln('<info>A ['.$name.'] symbolic link has been created in ['.$linkPath.'].</info>');
});

/**
 * Display all of the registered symbolic links.
 */
$app->command('links', function () {
    passthru('ls -la '.$_SERVER['HOME'].'/.valet/Sites');
});

/**
 * Unlink a link from the Valet links directory.
 */
$app->command('unlink name', function ($name, $output) {
    if (Valet\Site::unlink($name)) {
        $output->writeln('<info>The ['.$name.'] symbolic link has been removed.</info>');
    } else {
        $output->writeln('<fg=red>A symbolic link with this name does not exist.</>');
    }
});

/**
 * Add the current working directory to the paths configuration.
 */
$app->command('logs', function ($output) {
    $files = Valet\Site::logs();

    if (count($files) > 0) {
        passthru('tail -f '.implode(' ', $files));
    } else {
        $output->writeln('<fg=red>No log files were found.</>');
    }
});

/**
 * Display all of the registered paths.
 */
$app->command('paths', function ($output) {
    $paths = Valet\Configuration::read()['paths'];

    if (count($paths) > 0) {
        $output->writeln(json_encode($paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    } else {
        $output->writeln('No paths have been registered.');
    }
});

/**
 * Prune any non-existent paths.
 */
$app->command('prune', function ($output) {
    Valet\Configuration::prune();

    $output->writeln('<info>All missing paths have been pruned.</info>');
});

/**
 * Echo the currently tunneled URL.
 */
$app->command('fetch-share-url', function ($output) {
    retry(20, function () use ($output) {
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
 * Add the current working directory to the paths configuration.
 */
$app->command('restart', function ($output) {
    should_be_sudo();

    Valet\LaunchDaemon::restart();

    $output->writeln('<info>Valet services have been restarted.</info>');
});

/**
 * Add the current working directory to the paths configuration.
 */
$app->command('stop', function ($output) {
    should_be_sudo();

    Valet\LaunchDaemon::stop();

    $output->writeln('<info>Valet services have been stopped.</info>');
});

/**
 * Add the current working directory to the paths configuration.
 */
$app->command('uninstall', function ($output) {
    should_be_sudo();

    Valet\LaunchDaemon::uninstall();

    $output->writeln('<info>Valet has been uninstalled.</info>');
});

/**
 * Run the application.
 */
$app->run();
