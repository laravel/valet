<?php

use Illuminate\Container\Container;

class Facade
{
    /**
     * The key for the binding in the container.
     */
    public static function containerKey(): string
    {
        return 'Valet\\'.basename(str_replace('\\', '/', get_called_class()));
    }

    /**
     * Call a non-static method on the facade.
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        $resolvedInstance = Container::getInstance()->make(static::containerKey());

        return call_user_func_array([$resolvedInstance, $method], $parameters);
    }
}

class Brew extends Facade
{
}
class Nginx extends Facade
{
}
class CommandLine extends Facade
{
}
class Composer extends Facade
{
}
class Configuration extends Facade
{
}
class Diagnose extends Facade
{
}
class DnsMasq extends Facade
{
}
class Expose extends Facade
{
}
class Filesystem extends Facade
{
}
class Ngrok extends Facade
{
}
class PhpFpm extends Facade
{
}
class Site extends Facade
{
}
class Status extends Facade
{
}
class Upgrader extends Facade
{
}
class Valet extends Facade
{
}
