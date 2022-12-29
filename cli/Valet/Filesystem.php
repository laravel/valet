<?php

namespace Valet;

use CommandLine as CommandLineFacade;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Filesystem
{
    /**
     * Determine if the given path is a directory.
     *
     * @param  string  $path
     * @return bool
     */
    public function isDir(string $path): bool
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
    public function mkdir(string $path, ?string $owner = null, int $mode = 0755): void
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
    public function ensureDirExists(string $path, ?string $owner = null, int $mode = 0755): void
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
    public function mkdirAsUser(string $path, int $mode = 0755): void
    {
        $this->mkdir($path, user(), $mode);
    }

    /**
     * Touch the given path.
     *
     * @param  string  $path
     * @param  string|null  $owner
     * @return string
     */
    public function touch(string $path, ?string $owner = null): string
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
     * @return string
     */
    public function touchAsUser(string $path): string
    {
        return $this->touch($path, user());
    }

    /**
     * Determine if the given file exists.
     *
     * @param  string  $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Read the contents of the given file.
     *
     * @param  string  $path
     * @return string
     */
    public function get(string $path): string
    {
        return file_get_contents($path);
    }

    /**
     * Write to the given file.
     *
     * @param  string  $path
     * @param  string  $contents
     * @param  string|null  $owner
     * @return void
     */
    public function put(string $path, string $contents, ?string $owner = null): void
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
     * @param  string|null  $contents
     * @return void
     */
    public function putAsUser(string $path, ?string $contents): void
    {
        $this->put($path, $contents, user());
    }

    /**
     * Append the contents to the given file.
     *
     * @param  string  $path
     * @param  string  $contents
     * @param  string|null  $owner
     * @return void
     */
    public function append(string $path, string $contents, ?string $owner = null): void
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
    public function appendAsUser(string $path, string $contents): void
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
    public function copy(string $from, string $to): void
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
    public function copyAsUser(string $from, string $to): void
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
    public function symlink(string $target, string $link): void
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
    public function symlinkAsUser(string $target, string $link): void
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
    public function unlink(string $path): void
    {
        if (file_exists($path) || is_link($path)) {
            @unlink($path);
        }
    }

    /**
     * Recursively delete a directory and its contents.
     *
     * @param  string  $path
     * @return void
     */
    public function rmDirAndContents(string $path): void
    {
        $dir = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            if ($file->isLink()) {
                unlink($file->getPathname());
            } else {
                $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
            }
        }

        rmdir($path);
    }

    /**
     * Change the owner of the given path.
     *
     * @param  string  $path
     * @param  string  $user
     * @return void
     */
    public function chown(string $path, string $user): void
    {
        chown($path, $user);
    }

    /**
     * Change the group of the given path.
     *
     * @param  string  $path
     * @param  string  $group
     * @return void
     */
    public function chgrp(string $path, string $group): void
    {
        chgrp($path, $group);
    }

    /**
     * Resolve the given path.
     *
     * @param  string  $path
     * @return string
     */
    public function realpath(string $path): string
    {
        return realpath($path);
    }

    /**
     * Determine if the given path is a symbolic link.
     *
     * @param  string  $path
     * @return bool
     */
    public function isLink(string $path): bool
    {
        return is_link($path);
    }

    /**
     * Resolve the given symbolic link.
     *
     * @param  string  $path
     * @return string
     */
    public function readLink(string $path): string
    {
        return readlink($path);
    }

    /**
     * Remove all of the broken symbolic links at the given path.
     *
     * @param  string  $path
     * @return void
     */
    public function removeBrokenLinksAt(string $path): void
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
    public function isBrokenLink(string $path): bool
    {
        return is_link($path) && ! file_exists($path);
    }

    /**
     * Scan the given directory path.
     *
     * @param  string  $path
     * @return array
     */
    public function scandir(string $path): array
    {
        return collect(scandir($path))
                    ->reject(function ($file) {
                        return in_array($file, ['.', '..']);
                    })->values()->all();
    }

    /**
     * Get custom stub file if exists.
     *
     * @param  string  $filename
     * @return string
     */
    public function getStub(string $filename): string
    {
        $default = __DIR__.'/../stubs/'.$filename;

        $custom = VALET_HOME_PATH.'/stubs/'.$filename;

        $path = file_exists($custom) ? $custom : $default;

        return $this->get($path);
    }
}
