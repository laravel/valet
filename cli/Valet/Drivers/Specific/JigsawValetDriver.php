<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\BasicValetDriver;

class JigsawValetDriver extends BasicValetDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return is_dir($sitePath.'/build_local');
    }

    /**
     * Mutate the incoming URI.
     */
    public function mutateUri(string $uri): string
    {
        return rtrim('/build_local'.$uri, '/');
    }
}
