<?php

namespace Valet;

class WebContext
{
    public function __construct(public Filesystem $files)
    {
    }

    public function guessHomebrewPath(string $phpBinary): bool
    {
        $parts = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $phpBinary)));

        $currentDirectory = '';

        while($folder = array_shift($parts)) {
            $currentDirectory .= DIRECTORY_SEPARATOR . $folder;
            $brewExists = $currentDirectory . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'brew';
            $cellarExists = $currentDirectory . DIRECTORY_SEPARATOR . 'Cellar';

            $found = $this->files->exists($brewExists) && $this->files->isDir($cellarExists);

            if (! $found) {
                continue;
            }

            return $currentDirectory;
        }

        throw new \LogicException('Unable to guess homebrew path. Define a constant with BREW_PREFIX with your local homebrew path.');
    }
}

