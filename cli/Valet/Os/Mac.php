<?php

namespace Valet\Os;

use Valet\Os\Mac\Brew;
use function Valet\resolve;

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
}
