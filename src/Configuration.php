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

        static::write(['domain' => 'dev', 'paths' => []]);

        chown(static::path(), $_SERVER['SUDO_USER']);
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

        static::write($config);
    }

    /**
     * Add the given path to the configuration.
     *
     * @param  string  $path
     * @return void
     */
    public static function removePath($path)
    {
        $config = static::read();

        foreach ($config['paths'] as $key => $value) {
            if ($path === $value) {
                unset($config['paths'][$key]);
            }
        }

        $config['paths'] = array_unique(array_values($config['paths']));

        static::write($config);
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
     * Prune all non-existent paths from the configuration.
     *
     * @return void
     */
    public static function prune()
    {
        $config = static::read();

        foreach ($config['paths'] as $key => $path) {
            if (! is_dir($path)) {
                unset($config['paths'][$key]);
            }
        }

        $config['paths'] = array_values($config['paths']);

        static::write($config);
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
    public static function write(array $config)
    {
        file_put_contents(static::path(), json_encode($config, JSON_PRETTY_PRINT).PHP_EOL);
    }
}
