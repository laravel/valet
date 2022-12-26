<?php

namespace Valet\Os;

use Valet\Os\Linux\Apt;

use function Valet\resolve;

class Linux extends Os
{
    public function installer(): Installer
    {
        return resolve(Apt::class); // Constructor inject??
    }
}
