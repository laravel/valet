<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\BasicValetDriver;

class JoomlaValetDriver extends BasicValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return bool
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return is_dir($sitePath.'/libraries/joomla');
    }

    /**
     * Take any steps necessary before loading the front controller for this driver.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return void
     */
    public function beforeLoading(string $sitePath, string $siteName, string $uri): void
    {
        $_SERVER['PHP_SELF'] = $uri;
    }
}
