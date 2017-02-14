<?php

use Illuminate\Container\Container;

class Facade
{
    /**
     * The key for the binding in the container.
     *
     * @return string
     */
    public static function containerKey()
    {
        return 'Valet\\'.basename(str_replace('\\', '/', get_called_class()));
    }

    /**
     * Call a non-static method on the facade.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $resolvedInstance = Container::getInstance()->make(static::containerKey());

        return call_user_func_array([$resolvedInstance, $method], $parameters);
    }
}

class Nginx extends Facade {}
class PackageManager extends Facade {}
class Apt extends Facade {}
class Dnf extends Facade {}
class Pacman extends Facade {}
class ServiceManager extends Facade {}
class LinuxService extends Facade {}
class Systemd extends Facade {}
class CommandLine extends Facade {}
class Configuration extends Facade {}
class DnsMasq extends Facade {}
class Filesystem extends Facade {}
class Ngrok extends Facade {}
class PhpFpm extends Facade {}
class Site extends Facade {}
class Valet extends Facade {}
class Requirements extends Facade {}
