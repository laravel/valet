<?php

namespace Valet;

use CommandLine as CommandLineFacade;

class Filesystem
{
    private function toIterator($files)
    {
        if (!$files instanceof \Traversable) {
            $files = new \ArrayObject(is_array($files) ? $files : [$files]);
        }

        return $files;
    }

    /**
     * Delete the specified file or directory with files.
     *
     * @param string $files
     * @return void
     */
    public function remove($files)
    {
        $files = iterator_to_array($this->toIterator($files));
        $files = array_reverse($files);
        foreach ($files as $file) {
            if (!file_exists($file) && !is_link($file)) {
                continue;
            }

            if (is_dir($file) && !is_link($file)) {
                $this->remove(new \FilesystemIterator($file));

                if (true !== @rmdir($file)) {
                    throw new \Exception(sprintf('Failed to remove directory "%s".', $file), 0, null, $file);
                }
            } else {
                // https://bugs.php.net/bug.php?id=52176
                if ('\\' === DIRECTORY_SEPARATOR && is_dir($file)) {
                    if (true !== @rmdir($file)) {
                        throw new \Exception(sprintf('Failed to remove file "%s".', $file), 0, null, $file);
                    }
                } else {
                    if (true !== @unlink($file)) {
                        throw new \Exception(sprintf('Failed to remove file "%s".', $file), 0, null, $file);
                    }
                }
            }
        }
    }

    /**
     * Determine if the given path is a directory.
     *
     * @param string $path
     * @return bool
     */
    public function isDir($path)
    {
        return is_dir($path);
    }

    /**
     * Create a directory.
     *
     * @param string      $path
     * @param string|null $owner
     * @param int         $mode
     * @return void
     */
    public function mkdir($path, $owner = null, $mode = 0755)
    {
        if (!mkdir($path, $mode, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
        }

        if ($owner) {
            $this->chown($path, $owner);
        }
    }

    /**
     * Ensure that the given directory exists.
     *
     * @param string      $path
     * @param string|null $owner
     * @param int         $mode
     * @return void
     */
    public function ensureDirExists($path, $owner = null, $mode = 0755)
    {
        if (!$this->isDir($path)) {
            $this->mkdir($path, $owner, $mode);
        }
    }

    /**
     * Create a directory as the non-root user.
     *
     * @param string $path
     * @param int    $mode
     * @return void
     */
    public function mkdirAsUser($path, $mode = 0755)
    {
        return $this->mkdir($path, user(), $mode);
    }

    /**
     * Touch the given path.
     *
     * @param string      $path
     * @param string|null $owner
     * @return string
     */
    public function touch($path, $owner = null)
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
     * @param string $path
     * @return void
     */
    public function touchAsUser($path)
    {
        return $this->touch($path, user());
    }

    /**
     * Determine if the given file exists.
     *
     * @param string $path
     * @return bool
     */
    public function exists($files)
    {
        foreach ($this->toIterator($files) as $file) {
            if (!file_exists($file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Read the contents of the given file.
     *
     * @param string $path
     * @return string
     */
    public function get($path)
    {
        return file_get_contents($path);
    }

    /**
     * Write to the given file.
     *
     * @param string      $path
     * @param string      $contents
     * @param string|null $owner
     * @return string
     */
    public function put($path, $contents, $owner = null)
    {
        $status = file_put_contents($path, $contents);

        if ($owner) {
            $this->chown($path, $owner);
        }

        return $status;
    }

    /**
     * Write to the given file as the non-root user.
     *
     * @param string $path
     * @param string $contents
     * @return string
     */
    public function putAsUser($path, $contents)
    {
        return $this->put($path, $contents, user());
    }

    /**
     * Append the contents to the given file.
     *
     * @param string      $path
     * @param string      $contents
     * @param string|null $owner
     * @return void
     */
    public function append($path, $contents, $owner = null)
    {
        file_put_contents($path, $contents, FILE_APPEND);

        if ($owner) {
            $this->chown($path, $owner);
        }
    }

    /**
     * Append the contents to the given file as the non-root user.
     *
     * @param string $path
     * @param string $contents
     * @return void
     */
    public function appendAsUser($path, $contents)
    {
        $this->append($path, $contents, user());
    }

    /**
     * Copy the given file to a new location.
     *
     * @param string $from
     * @param string $to
     * @return void
     */
    public function copy($from, $to)
    {
        copy($from, $to);
    }

    /**
     * Backup the given file.
     *
     * @param string $file
     * @return bool
     */
    public function backup($file)
    {
        $to = $file . '.bak';

        if (!$this->exists($to)) {
            if ($this->exists($file)) {
                return rename($file, $to);
            }
        }

        return false;
    }

    /**
     * Restore a backed up file.
     *
     * @param string $file
     * @return bool
     */
    public function restore($file)
    {
        $from = $file . '.bak';

        if ($this->exists($from)) {
            return rename($from, $file);
        }

        return false;
    }

    /**
     * Copy the given file to a new location for the non-root user.
     *
     * @param string $from
     * @param string $to
     * @return void
     */
    public function copyAsUser($from, $to)
    {
        copy($from, $to);

        $this->chown($to, user());
    }

    /**
     * Create a symlink to the given target.
     *
     * @param string $target
     * @param string $link
     * @return void
     */
    public function symlink($target, $link)
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
     * @param string $target
     * @param string $link
     * @return void
     */
    public function symlinkAsUser($target, $link)
    {
        if ($this->exists($link)) {
            $this->unlink($link);
        }

        CommandLineFacade::runAsUser('ln -s ' . escapeshellarg($target) . ' ' . escapeshellarg($link));
    }

    /**
     * Comment a line in a file.
     *
     * @param string $line
     * @param string $file
     * @return void
     */
    public function commentLine($line, $file)
    {
        if ($this->exists($file)) {
            $command = "sed -i '/{$line}/ s/^/# /' {$file}";
            CommandLineFacade::run($command);
        }
    }

    /**
     * Uncomment a line in a file.
     *
     * @param string $line
     * @param string $file
     * @return void
     */
    public function uncommentLine($line, $file)
    {
        if ($this->exists($file)) {
            $command = "sed -i '/{$line}/ s/# *//' {$file}";
            CommandLineFacade::run($command);
        }
    }

    /**
     * Delete the file at the given path.
     *
     * @param string $path
     * @return void
     */
    public function unlink($path)
    {
        if (file_exists($path) || is_link($path)) {
            @unlink($path);
        }
    }

    /**
     * Change the owner of the given path.
     *
     * @param string $path
     * @param string $user
     */
    public function chown($path, $user)
    {
        chown($path, $user);
    }

    /**
     * Change the group of the given path.
     *
     * @param string $path
     * @param string $group
     */
    public function chgrp($path, $group)
    {
        chgrp($path, $group);
    }

    /**
     * Resolve the given path.
     *
     * @param string $path
     * @return string
     */
    public function realpath($path)
    {
        return realpath($path);
    }

    /**
     * Determine if the given path is a symbolic link.
     *
     * @param string $path
     * @return bool
     */
    public function isLink($path)
    {
        return is_link($path);
    }

    /**
     * Resolve the given symbolic link.
     *
     * @param string $path
     * @return string
     */
    public function readLink($path)
    {
        $link = $path;

        while (is_link($link)) {
            $link = readlink($link);
        }

        return $link;
    }

    /**
     * Remove all of the broken symbolic links at the given path.
     *
     * @param string $path
     * @return void
     */
    public function removeBrokenLinksAt($path)
    {
        collect($this->scandir($path))
            ->filter(function ($file) use ($path) {
                return $this->isBrokenLink($path . '/' . $file);
            })
            ->each(function ($file) use ($path) {
                $this->unlink($path . '/' . $file);
            });
    }

    /**
     * Determine if the given path is a broken symbolic link.
     *
     * @param string $path
     * @return bool
     */
    public function isBrokenLink($path)
    {
        return is_link($path) && !file_exists($path);
    }

    /**
     * Scan the given directory path.
     *
     * @param string $path
     * @return array
     */
    public function scandir($path)
    {
        return collect(scandir($path))
            ->reject(function ($file) {
                return in_array($file, ['.', '..']);
            })->values()->all();
    }
}
