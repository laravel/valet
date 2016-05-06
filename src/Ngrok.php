<?php

namespace Valet;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Ngrok
{
    /**
     * Install and configure DnsMasq.
     *
     * @param  OutputInterface  $output
     * @return void
     */
    public static function install($output)
    {
        if (! static::alreadyInstalled()) {
            static::download($output);
        }
    }

    /**
     * @return bool
     */
    public static function alreadyInstalled()
    {
        return file_exists(__DIR__.'/bin/ngrok');
    }

    /**
     * @param string $output
     * @throws Exception
     */
    public static function download($output)
    {
        $output->writeln(Compatibility::get('NGROK_INSTALL_TEXT'));

        $downloadPath = Compatibility::get('NGROK_INSTALL');
        $fileName = __DIR__.'/../bin/ngrok.zip';

        if (!file_exists($fileName)) {
            file_put_contents($fileName, file_get_contents($downloadPath));

            $process = new Process(sprintf(Compatibility::get('NGROK_UNZIP'), $fileName));

            $processOutput = '';
            $process->run(function ($type, $line) use (&$processOutput) {
                $processOutput .= $line;
            });

            if ($process->getExitCode() > 0) {
                $output->write($processOutput);

                throw new Exception('We were unable to install ngrok.');
            }

            unlink($fileName);
            chown(__DIR__.'/../ngrok', $_SERVER['SUDO_USER']);
            rename(__DIR__.'/../ngrok', __DIR__.'/../bin/ngrok');


            $output->writeln('');
        }
    }
}

