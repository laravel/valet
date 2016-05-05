<?php

namespace Valet;

use Exception;

class Site
{
    /**
     * Link the current working directory with the given name.
     *
     * @param  string  $name
     * @return string
     */
    public static function link($name)
    {
        if (! is_dir($linkPath = $_SERVER['HOME'].'/.valet/Sites')) {
            mkdir($linkPath, 0755);
        }

        Configuration::addPath($linkPath);

        if (file_exists($linkPath.'/'.$name)) {
            throw new Exception("A symbolic link with this name already exists.");
        }

        symlink(getcwd(), $linkPath.'/'.$name);

        return $linkPath;
    }

    /**
     * Unlink the given symbolic link.
     *
     * @param  string  $name
     * @return void
     */
    public static function unlink($name)
    {
        quietly('rm '.$_SERVER['HOME'].'/.valet/Sites/'.$name);

        return true;
    }

    /**
     * Remove all broken symbolic links.
     *
     * @return void
     */
    public static function pruneLinks()
    {
        foreach (scandir($_SERVER['HOME'].'/.valet/Sites') as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }

            if (is_link($linkPath = $_SERVER['HOME'].'/.valet/Sites/'.$file) && ! file_exists($linkPath)) {
                quietly('rm '.$linkPath);
            }
        }
    }

    /**
     * Get all of the log files for all sites.
     *
     * @return array
     */
    public static function logs()
    {
        $paths = Configuration::read()['paths'];

        $files = [];

        foreach ($paths as $path) {
            foreach (scandir($path) as $directory) {
                $logPath = $path.'/'.$directory.'/storage/logs/laravel.log';

                if (in_array($directory, ['.', '..'])) {
                    continue;
                }

                if (file_exists($logPath)) {
                    $files[] = $logPath;
                } elseif (is_dir(dirname($logPath))) {
                    touch($logPath);

                    $files[] = $logPath;
                }
            }
        }

        return $files;
    }
}
