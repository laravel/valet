<?php

namespace Valet;

class Filesystem
{
    /**
     * Determine if the given path is a directory.
     *
     * @param  string  $path
     * @return bool
     */
    public function isDir($path)
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
    public function mkdir($path, $owner = null, $mode = 0755)
    {
        mkdir($path, $mode);

        if ($owner) {
            $this->chown($path, $owner);
        }
    }

    /**
     * Create a directory as the non-root user.
     *
     * @param  string  $path
     * @param  int  $mode
     * @return void
     */
    public function mkdirAsUser($path, $mode = 0755)
    {
        return $this->mkdir($path, user(), $mode);
    }

    /**
     * Touch the given path.
     *
     * @param  string  $path
     * @param  string|null  $owner
     * @return void
     */
    public function touch($path, $owner = null)
    {
        touch($path);

        if ($owner) {
            $this->chown($path, $owner);
        }
    }

    /**
     * Touch the given path as the non-root user.
     *
     * @param  string  $path
     * @return void
     */
    public function touchAsUser($path)
    {
        return $this->touch($path, user());
    }

    /**
     * Determine if the given file exists.
     *
     * @param  string  $path
     * @return bool
     */
    public function exists($path)
    {
        return file_exists($path);
    }

    /**
     * Read the contents of the given file.
     *
     * @param  string  $path
     * @return string
     */
    public function get($path)
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
    public function put($path, $contents, $owner = null)
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
    public function putAsUser($path, $contents)
    {
        return $this->put($path, $contents, user());
    }

    /**
     * Delete the file at the given path.
     *
     * @param  string  $path
     * @return void
     */
    public function unlink($path)
    {
        @unlink($path);
    }

    /**
     * Change the owner of the given path.
     *
     * @param  string  $path
     * @param  string  $user
     */
    public function chown($path, $user)
    {
        chown($path, $user);
    }

    /**
     * Change the group of the given path.
     *
     * @param  string  $path
     * @param  string  $group
     */
    public function chgrp($path, $group)
    {
        chgrp($path, $group);
    }

    /**
     * Resolve the given path.
     *
     * @param  string  $path
     * @return string
     */
    public function realpath($path)
    {
        return realpath($path);
    }

    /**
     * Determine if the given path is a symbolic link.
     *
     * @param  string  $path
     * @return bool
     */
    public function isLink($path)
    {
        return is_link($path);
    }

    /**
     * Resolve the given symbolic link.
     *
     * @param  string  $path
     * @return string
     */
    public function readLink($path)
    {
        return read_link($path);
    }
}
