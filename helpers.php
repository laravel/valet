<?php

use Symfony\Component\Process\Process;

define('VALET_HOME_PATH', $_SERVER['HOME'].'/.valet');

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
        if (! $retries) {
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
 * Run the given command.
 *
 * @param  string  $command
 * @param  callable $onError
 * @return string
 */
function run($command, callable $onError = null)
{
    return run_as_root('sudo -u '.$_SERVER['SUDO_USER'].' '.$command, $onError);
}

/**
 * Run the given command as root.
 *
 * @param  string  $command
 * @param  callable $onError
 * @return string
 */
function run_as_root($command, callable $onError = null)
{
    $onError = $onError ?: function () {};

    $process = new Process($command);

    $processOutput = '';
    $process->run(function ($type, $line) use (&$processOutput) {
        $processOutput .= $line;
    });

    if ($process->getExitCode() > 0) {
        $onError($process->getExitCode(), $processOutput);
    }

    return $processOutput;
}

/**
 * Verify that the script is currently running as "sudo".
 *
 * @return void
 */
function should_be_sudo()
{
    if (! isset($_SERVER['SUDO_USER'])) {
        throw new Exception('This command must be run with sudo.');
    }
}
