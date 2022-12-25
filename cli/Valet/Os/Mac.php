<?php

namespace Valet\Os;

use Valet\Os\Mac\Brew;
use function Valet\resolve;

class Mac
{
    public function installer(): Installer
    {
        return resolve(Brew::class); // Constructor inject??
    }
}
