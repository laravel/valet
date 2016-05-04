<?php

namespace Valet;

class Configuration
{
    /**
     * Install the Valet configuration file.
     *
     * @return void
     */
    public static function install()
    {
        if (! is_dir($directory = $_SERVER['HOME'].'/.valet')) {
            mkdir($directory, 0755);

            chown($directory, $_SERVER['SUDO_USER']);
        }

        static::write(['domain' => 'dev', 'paths' => []], true);
    }

    /**
     * Add the given path to the configuration.
     *
     * @param  string  $path
     * @return void
     */
    public static function addPath($path)
    {
        $config = static::read();

        $config['paths'] = array_unique(array_merge($config['paths'], [$path]));

        static::write($config, true);
    }

    /**
     * Get the configuration file path.
     *
     * @return string
     */
    public static function path()
    {
        return $_SERVER['HOME'].'/.valet/config.json';
    }

    /**
     * Read the configuration file as JSON.
     *
     * @return array
     */
    public static function read()
    {
        return json_decode(file_get_contents(static::path()), true);
    }

    /**
     * Write the given configuration to disk.
     *
     * @param  array  $config
     * @return void
     */
    public static function write(array $config, $chown = false)
    {
        file_put_contents(static::path(), json_encode($config, JSON_PRETTY_PRINT).PHP_EOL);

        if ($chown) {
            chown(static::path(), $_SERVER['SUDO_USER']);
        }
    }
}
