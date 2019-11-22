<?php

namespace Valet\Tests\Functional;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

class FunctionalTestCase extends TestCase
{
    /**
     * Execute valet command.
     * Fail if exit code is different from 0.
     *
     * @param string      $command
     * @param null|string $workingDir
     * @return string
     */
    protected function valetCommand($command, $workingDir = null)
    {
        return $this->exec($this->valet() . ' ' . $command, $workingDir);
    }

    /**
     * Get valet prefix for commands.
     *
     * @return string
     */
    protected function valet()
    {
        if (isset($_SERVER['REPOSITORY'])) {
            return $_SERVER['REPOSITORY'] . '/valet';
        }

        return 'valet';
    }

    /**
     * Pass the command to the command line and display the output.
     * Fail if exit code is different from 0.
     *
     * @param string      $command
     * @param null|string $workingDir
     * @return string
     */
    protected function exec($command, $workingDir = null)
    {
        $process = new Process($command);
        $process->setWorkingDirectory(is_null($workingDir) ? realpath(__DIR__ . '/../..') : $workingDir);

        $processOutput = '';
        $process->setTimeout(null)->run(function ($type, $line) use (&$processOutput) {
            $processOutput .= $line;
        });

        if ($process->getExitCode() > 0) {
            throw new RuntimeException(
                'Command "' . $command . '" exited with exit code ' . $process->getExitCode() . PHP_EOL .
                $processOutput
            );
        }

        return $processOutput;
    }

    /**
     * Run a command in the background.
     *
     * @param string      $command
     * @param null|string $workingDir
     * @return Process
     */
    protected function background($command, $workingDir = null)
    {
        $process = new Process($command);

        $process
            ->setWorkingDirectory(
                is_null($workingDir) ? realpath(__DIR__ . '/../..') : $workingDir
            )
            ->setTimeout(null)
            ->start();

        return $process;
    }
}
