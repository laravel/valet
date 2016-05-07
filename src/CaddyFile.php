<?php

namespace Valet;

class CaddyFile
{
    /**
     * Install the Caddy configuration file.
     *
     * @return void
     */
    public static function install()
    {
        copy(__DIR__.'/../stubs/Caddyfile', VALET_HOME_PATH.'/Caddyfile');
    }
}
