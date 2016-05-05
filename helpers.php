<?php

use Symfony\Component\Process\Process;
use Valet\Compatibility;

/**
 * Simple global function to run commands.
 */
function quietly($command)
{
    (new Process($command))->run();
}

/**
 * Retry the given function N times.
 *
 * @param  int  $retries
 * @param  callable  $retries
 * @param  int  $sleep
 * @return mixed
 */
function retry($retries, $fn, $sleep = 0)
{
    beginning:
    try {
        return $fn();
    } catch (Exception $e) {
        if (!$retries) {
            throw $e;
        }
        $retries--;
        if ($sleep > 0) {
            usleep($sleep * 1000);
        }
        goto beginning;
    }
}

/**
 * Check the system's compatibility with Valet.
 *
 * @return bool
 */
function should_be_compatible()
{
    if (PHP_OS != 'Linux' && PHP_OS != 'Darwin') {
        echo 'Valet only supports the Mac or Linux operating systems.'.PHP_EOL;

        exit(1);
    }

    if (version_compare(PHP_VERSION, '5.5.9', '<')) {
        echo "Valet requires PHP 5.5.9 or later.";

        exit(1);
    }

    if (exec(Compatibility::get('WHICH_INSTALLER')) != Compatibility::get('WHICH_INSTALLER')) {
        echo Compatibility::get('WHICH_INSTALLER_ERROR');

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
