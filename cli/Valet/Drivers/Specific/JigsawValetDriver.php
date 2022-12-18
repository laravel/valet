<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\BasicValetDriver;

class JigsawValetDriver extends BasicValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return void
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return is_dir($sitePath.'/build_local');
    }

    /**
     * Mutate the incoming URI.
     *
     * @param  string  $uri
     * @return string
     */
    public function mutateUri(string $uri): string
    {
        return rtrim('/build_local'.$uri, '/');
    }
}
