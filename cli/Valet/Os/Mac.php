<?php

namespace Valet\Os;

use Valet\Os\Mac\Brew;
use Valet\Os\Mac\MacStatus;
use function Valet\resolve;
use Valet\Status;

class Mac extends Os
{
    public function installer(): Installer
    {
        return resolve(Brew::class);
    }

    public function etcDir(): string
    {
        return BREWAPT_PREFIX.'/etc';
    }

    public function logDir(): string
    {
        return BREWAPT_PREFIX.'/var/log';
    }

    public function status(): Status
    {
        return resolve(MacStatus::class);
    }
}
