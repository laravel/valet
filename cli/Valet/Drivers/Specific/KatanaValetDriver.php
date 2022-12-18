<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\BasicValetDriver;

class KatanaValetDriver extends BasicValetDriver
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
        return file_exists($sitePath.'/katana');
    }

    /**
     * Mutate the incoming URI.
     *
     * @param  string  $uri
     * @return string
     */
    public function mutateUri(string $uri): string
    {
        return rtrim('/public'.$uri, '/');
    }
}
