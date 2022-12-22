<?php

use Illuminate\Container\Container;

trait UsesNullWriter
{
    public function setNullWriter()
    {
        Container::getInstance()->instance('writer', new NullWriter);
    }
}

class NullWriter
{
    public function writeLn($msg)
    {
        // do nothing
    }
}
