<?php

namespace Valet;

use Symfony\Component\Process\Process;

class CommandLine
{
    /**
     * Simple global function to run commands.
     *
     * @param  string  $command
     * @return void
     */
    function quietly($command)
    {
        $this->runCommand($command.' > /dev/null 2>&1');
    }

    /**
     * Simple global function to run commands.
     *
     * @param  string  $command
     * @return void
     */
    function quietlyAsUser($command)
    {
        $this->quietly('sudo -u '.user().' '.$command.' > /dev/null 2>&1');
    }

    /**
     * Pass the command to the command line and display the output.
     *
     * @param  string  $command
     * @return void
     */
    function passthru($command)
    {
        passthru($command);
    }

    /**
     * Run the given command as the non-root user.
     *
     * @param  string  $command
     * @param  callable $onError
     * @return string
     */
    function run($command, callable $onError = null)
    {
        return $this->runCommand($command, $onError);
    }

    /**
     * Run the given command.
     *
     * @param  string  $command
     * @param  callable $onError
     * @return string
     */
    function runAsUser($command, callable $onError = null)
    {
        return $this->runCommand('sudo -u '.user().' '.$command, $onError);
    }

    /**
     * Run the given command.
     *
     * @param  string  $command
     * @param  callable $onError
     * @return string
     */
    function runCommand($command, callable $onError = null)
    {
        $onError = $onError ?: function () {};

        $process = new Process($command);

        $processOutput = '';
        $process->setTimeout(null)->run(function ($type, $line) use (&$processOutput) {
            $processOutput .= $line;
        });

        if ($process->getExitCode() > 0) {
            $onError($process->getExitCode(), $processOutput);
        }

        return $processOutput;
    }
}
