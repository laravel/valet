<?php

use Illuminate\Container\Container;
use Valet\Os\Installer;
use Valet\Os\Mac;
use Valet\Os\Mac\Brew;
use function Valet\resolve;

trait PrepsContainer
{
    public function prepContainer()
    {
        Container::setInstance($container = new Container);

        $container->instance('os', resolve(Mac::class));
        $container->instance(Installer::class, resolve(Brew::class));
    }
}
