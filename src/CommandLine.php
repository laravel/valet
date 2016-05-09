<?php

namespace Valet;

use Symfony\Component\Process\Process;

class CommandLine
{
    /**
     * Simple global function to run commands.
     */
    public function quietly($command)
    {
        (new Process($command))->run();
    }

    /**
     * Pass the command to the command line and display the output.
     *
     * @param  string  $command
     * @return void
     */
    public function passthru($command)
    {
        passthru($command);
    }

    /**
     * Run the given command.
     *
     * @param  string  $command
     * @param  callable $onError
     * @return string
     */
    public function run($command, callable $onError = null)
    {
        return $this->runAsRoot('sudo -u '.$_SERVER['SUDO_USER'].' '.$command, $onError);
    }

    /**
     * Run the given command as root.
     *
     * @param  string  $command
     * @param  callable $onError
     * @return string
     */
    public function runAsRoot($command, callable $onError = null)
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
}
