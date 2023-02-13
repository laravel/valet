<?php

namespace Valet\Os;

use Valet\Os\Linux\Apt;
use Valet\Os\Linux\LinuxStatus;
use Valet\Status;

use function Valet\resolve;

class Linux extends Os
{
    public function installer(): Installer
    {
        return resolve(Apt::class);
    }

    public function etcDir(): string
    {
        return '/etc';
    }

    public function logDir(): string
    {
        return '/log';
    }

    public function status(): Status
    {
        return resolve(LinuxStatus::class);
    }
}
