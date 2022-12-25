<?php

use Illuminate\Container\Container;
use Valet\Os\Installer;
use Valet\Os\Os;

trait PrepsContainer
{
    public function prepContainer()
    {
        Container::setInstance($container = new Container);
        $os = Os::assign();
        $container->instance(Installer::class, $os->installer());
    }
}
