<?php

namespace Valet\Commands;

use Configuration;
use DnsMasq;
use Nginx;
use PhpFpm;
use Site;
use Symfony\Component\Console\Input\InputArgument;

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

    public function fire()
    {
        $tld = $this->input->getArgument('tld');

        if ($tld === null) {
            return $this->output(Configuration::read()['tld']);
        }

        DnsMasq::updateTld(
            $oldTld = Configuration::read()['tld'],
            $tld = trim($tld, '.')
        );

        Configuration::updateKey('tld', $tld);

        Site::resecureForNewConfiguration(['tld' => $oldTld], ['tld' => $tld]);
        PhpFpm::restart();
        Nginx::restart();

        $this->info('Your Valet TLD has been updated to ['.$tld.'].');
    }
}
