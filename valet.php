#!/usr/bin/env php
<?php

if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require __DIR__.'/vendor/autoload.php';
} else {
    require __DIR__.'/../../autoload.php';
}

should_be_compatible();

define('VALET_HOME_PATH', $_SERVER['HOME'].'/.valet');

use Silly\Application;

/**
 * Create the application.
 */
$app = new Application('Laravel Valet', 'v1.0.12');

/**
 * Prune missing directories and symbolic links on every command.
 */
if (is_dir(VALET_HOME_PATH)) {
    Valet\Configuration::prune();
    Valet\Site::pruneLinks();
}

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
 * Change the domain currently being used by Valet.
 */
$app->command('domain domain', function ($domain, $output) {
    should_be_sudo();

    $domain = trim($domain, '.');

    Valet\DnsMasq::updateDomain(Valet\Configuration::read()['domain'], $domain);

    Valet\Configuration::updateKey('domain', $domain);

    $output->writeln('<info>Your Valet domain has been updated to ['.$domain.'].</info>');
});

/**
 * Change the sites manifest url currently being used by Valet.
 */
$app->command('manifest manifest', function ($manifest, $output) {
    Valet\Configuration::updateKey('manifest', $manifest);

    $domain = Valet\Configuration::read()['domain'];

    $output->writeln('<info>Your Valet manifest url has been updated to [http://'.$manifest.'.'.$domain.'].</info>');
});

/**
 * Get the domain currently being used by Valet.
 */
$app->command('current-domain', function ($output) {
    $output->writeln(Valet\Configuration::read()['domain']);
});

/**
 * Add the current working directory to the paths configuration.
 */
$app->command('park', function ($output) {
    Valet\Configuration::addPath(getcwd());

    $output->writeln("<info>This directory has been added to Valet's paths.</info>");
});

/**
 * Remove the current working directory to the paths configuration.
 */
$app->command('forget', function ($output) {
    Valet\Configuration::removePath(getcwd());

    $output->writeln("<info>This directory has been removed from Valet's paths.</info>");
});

/**
 * Register a symbolic link with Valet.
 */
$app->command('link [name]', function ($name, $output) {
    $name = $name ?: basename(getcwd());

    $linkPath = Valet\Site::link($name);

    $output->writeln('<info>A ['.$name.'] symbolic link has been created in ['.$linkPath.'].</info>');
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
$app->command('unlink [name]', function ($name, $output) {
    $name = $name ?: basename(getcwd());

    if (Valet\Site::unlink($name)) {
        $output->writeln('<info>The ['.$name.'] symbolic link has been removed.</info>');
    } else {
        $output->writeln('<fg=red>A symbolic link with this name does not exist.</>');
    }
});

/**
 * Determine which Valet driver the current directory is using.
 */
$app->command('which', function ($output) {
    require __DIR__.'/drivers/require.php';

    $driver = ValetDriver::assign(getcwd(), basename(getcwd()), '/');

    if ($driver) {
        $output->writeln('<info>This site is served by ['.get_class($driver).'].</info>');
    } else {
        $output->writeln('<fg=red>Valet could not determine which driver to use for this site.</>');
    }
});

/**
 * Stream all of the logs for all sites.
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
 * Start the daemon services.
 */
$app->command('start', function ($output) {
    should_be_sudo();

    Valet\LaunchDaemon::restart();

    $output->writeln('<info>Valet services have been started.</info>');
});

/**
 * Restart the daemon services.
 */
$app->command('restart', function ($output) {
    should_be_sudo();

    Valet\LaunchDaemon::restart();

    $output->writeln('<info>Valet services have been restarted.</info>');
});

/**
 * Stop the daemon services.
 */
$app->command('stop', function ($output) {
    should_be_sudo();

    Valet\LaunchDaemon::stop();

    $output->writeln('<info>Valet services have been stopped.</info>');
});

/**
 * Uninstall Valet entirely.
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
