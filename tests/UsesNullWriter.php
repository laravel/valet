<?php

use Illuminate\Container\Container;

trait UsesNullWriter
{
    public function setNullWriter()
    {
        Container::getInstance()->instance('writer', new class
        {
            public function writeLn($msg)
            {
                // do nothing
            }
        });
    }
}
