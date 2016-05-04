<?php

use Symfony\Component\Process\Process;

/**
 * Simple global function to run commands.
 */
function quietly($command)
{
    (new Process($command))->run();
}

/**
 * Check the system's compatibility with Valet.
 *
 * @return bool
 */
function should_be_compatible()
{
    if (PHP_OS != 'Darwin') {
        echo 'Valet only supports the Mac operating system.'.PHP_EOL;

        exit(1);
    }

    if (exec('which php') != '/usr/local/bin/php') {
        echo "Valet requires PHP to be installed at [/usr/local/bin/php].";

        exit(1);
    }

    if (PHP_MAJOR_VERSION < 7) {
        echo "Valet requires PHP 7.0 or later.";

        exit(1);
    }

    if (exec('which brew') != '/usr/local/bin/brew') {
        echo 'Valet requires Brew to be installed on your Mac.';

        exit(1);
    }
}

/**
 * Verify that a command is being run as "sudo".
 *
 * @return void
 */
function should_be_sudo()
{
    if (! isset($_SERVER['SUDO_USER'])) {
        throw new Exception('This command must be run with sudo.');
    }
}
