<?php

use Illuminate\Container\Container;
use Valet\Context\Context;
use Valet\Context\Standalone;
use function Valet\resolve;

trait PrepsContainer
{
    public function prepContainer()
    {
        Container::setInstance($container = new Container);

        $container->instance(Context::class, resolve(Standalone::class));
    }
}
