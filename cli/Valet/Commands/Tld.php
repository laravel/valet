<?php

namespace Valet\Commands;

use Configuration;
use DnsMasq;
use Nginx;
use PhpFpm;
use Site;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use function Valet\info;
use function Valet\output;

class Tld extends Command
{
    protected function configure()
    {
        $this->setName('tld')
            ->setDescription('Get or set the TLD used for Valet sites.')
            ->addArgument(
                'tld',
                InputArgument::OPTIONAL,
            );
    }

    protected function fire()
    {
    }

    public function execute(Input $input, Output $output)
    {
        $output->writeLn('test');

        return 0;

        $tld = $input->getArgument('tld');

        if ($tld === null) {
            return output(Configuration::read()['tld']);
        }

        DnsMasq::updateTld(
            $oldTld = Configuration::read()['tld'],
            $tld = trim($tld, '.')
        );

        Configuration::updateKey('tld', $tld);

        Site::resecureForNewConfiguration(['tld' => $oldTld], ['tld' => $tld]);
        PhpFpm::restart();
        Nginx::restart();

        info('Your Valet TLD has been updated to ['.$tld.'].');
    }
}
