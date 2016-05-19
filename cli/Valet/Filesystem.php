<?php

namespace Valet;

use CommandLine as CommandLineFacade;

class Filesystem
{
    /**
     * Determine if the given path is a directory.
     *
     * @param  string  $path
     * @return bool
     */
    function isDir($path)
    {
        return is_dir($path);
    }

    /**
     * Create a directory.
     *
     * @param  string  $path
     * @param  string|null  $owner
     * @param  int  $mode
     * @return void
     */
    function mkdir($path, $owner = null, $mode = 0755)
    {
        mkdir($path, $mode, true);

        if ($owner) {
            $this->chown($path, $owner);
        }
    }

    /**
     * Ensure that the given directory exists.
     *
     * @param  string  $path
     * @param  string|null  $owner
     * @param  int  $mode
     * @return void
     */
    function ensureDirExists($path, $owner = null, $mode = 0755)
    {
        if (! $this->isDir($path)) {
            $this->mkdir($path, $owner, $mode);
        }
    }

    /**
     * Create a directory as the non-root user.
     *
     * @param  string  $path
     * @param  int  $mode
     * @return void
     */
    function mkdirAsUser($path, $mode = 0755)
    {
        return $this->mkdir($path, user(), $mode);
    }

    /**
     * Touch the given path.
     *
     * @param  string  $path
     * @param  string|null  $owner
     * @return string
     */
    function touch($path, $owner = null)
    {
        touch($path);

        if ($owner) {
            $this->chown($path, $owner);
        }

        return $path;
    }

    /**
     * Touch the given path as the non-root user.
     *
     * @param  string  $path
     * @return void
     */
    function touchAsUser($path)
    {
        return $this->touch($path, user());
    }

    /**
     * Determine if the given file exists.
     *
     * @param  string  $path
     * @return bool
     */
    function exists($path)
    {
        return file_exists($path);
    }

    /**
     * Read the contents of the given file.
     *
     * @param  string  $path
     * @return string
     */
    function get($path)
    {
        return file_get_contents($path);
    }

    /**
     * Write to the given file.
     *
     * @param  string  $path
     * @param  string  $contents
     * @param  string|null  $owner
     * @return string
     */
    function put($path, $contents, $owner = null)
    {
        file_put_contents($path, $contents);

        if ($owner) {
            $this->chown($path, $owner);
        }
    }

    /**
     * Write to the given file as the non-root user.
     *
     * @param  string  $path
     * @param  string  $contents
     * @return string
     */
    function putAsUser($path, $contents)
    {
        return $this->put($path, $contents, user());
    }

    /**
     * Append the contents to the given file.
     *
     * @param  string  $path
     * @param  string  $contents
     * @param  string|null  $owner
     * @return void
     */
    function append($path, $contents, $owner = null)
    {
        file_put_contents($path, $contents, FILE_APPEND);

        if ($owner) {
            $this->chown($path, $owner);
        }
    }

    /**
     * Append the contents to the given file as the non-root user.
     *
     * @param  string  $path
     * @param  string  $contents
     * @return void
     */
    function appendAsUser($path, $contents)
    {
        $this->append($path, $contents, user());
    }

    /**
     * Copy the given file to a new location.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    function copy($from, $to)
    {
        copy($from, $to);
    }

    /**
     * Copy the given file to a new location for the non-root user.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    function copyAsUser($from, $to)
    {
        copy($from, $to);

        $this->chown($to, user());
    }

    /**
     * Create a symlink to the given target.
     *
     * @param  string  $target
     * @param  string  $link
     * @return void
     */
    function symlink($target, $link)
    {
        if ($this->exists($link)) {
            $this->unlink($link);
        }

        symlink($target, $link);
    }

    /**
     * Create a symlink to the given target for the non-root user.
     *
     * This uses the command line as PHP can't change symlink permissions.
     *
     * @param  string  $target
     * @param  string  $link
     * @return void
     */
    function symlinkAsUser($target, $link)
    {
        if ($this->exists($link)) {
            $this->unlink($link);
        }

        CommandLineFacade::runAsUser('ln -s '.escapeshellarg($target).' '.escapeshellarg($link));
    }

    /**
     * Delete the file at the given path.
     *
     * @param  string  $path
     * @return void
     */
    function unlink($path)
    {
        if (file_exists($path) || is_link($path)) {
            @unlink($path);
        }
    }

    /**
     * Change the owner of the given path.
     *
     * @param  string  $path
     * @param  string  $user
     */
    function chown($path, $user)
    {
        chown($path, $user);
    }

    /**
     * Change the group of the given path.
     *
     * @param  string  $path
     * @param  string  $group
     */
    function chgrp($path, $group)
    {
        chgrp($path, $group);
    }

    /**
     * Resolve the given path.
     *
     * @param  string  $path
     * @return string
     */
    function realpath($path)
    {
        return realpath($path);
    }

    /**
     * Determine if the given path is a symbolic link.
     *
     * @param  string  $path
     * @return bool
     */
    function isLink($path)
    {
        return is_link($path);
    }

    /**
     * Resolve the given symbolic link.
     *
     * @param  string  $path
     * @return string
     */
    function readLink($path)
    {
        return readlink($path);
    }

    /**
     * Remove all of the broken symbolic links at the given path.
     *
     * @param  string  $path
     * @return void
     */
    function removeBrokenLinksAt($path)
    {
        collect($this->scandir($path))
                ->filter(function ($file) use ($path) {
                    return $this->isBrokenLink($path.'/'.$file);
                })
                ->each(function ($file) use ($path) {
                    $this->unlink($path.'/'.$file);
                });
    }

    /**
     * Determine if the given path is a broken symbolic link.
     *
     * @param  string  $path
     * @return bool
     */
    function isBrokenLink($path)
    {
        return is_link($path) && ! file_exists($path);
    }

    /**
     * Scan the given directory path.
     *
     * @param  string  $path
     * @return array
     */
    function scandir($path)
    {
        return collect(scandir($path))
                    ->reject(function ($file) {
                        return in_array($file, ['.', '..']);
                    })->values()->all();
    }
}
