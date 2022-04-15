<?php

namespace Valet\Commands;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SymfonyCommand
{
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        return (int) $this->fire();
    }

    public function output($string)
    {
        $this->output->writeLn($string);
    }

    public function info($string)
    {
        $this->output('<info>'.$string.'</info>');
    }

    abstract protected function fire();
}
