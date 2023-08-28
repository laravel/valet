<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\LaravelValetDriver;

class StatamicV3ValetDriver extends LaravelValetDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath.'/please')
            && parent::serves($sitePath, $siteName, $uri);
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
    {
        if ($this->isActualFile($staticPath = $this->getStaticPath($sitePath))) {
            return $staticPath;
        }

        return parent::frontControllerPath($sitePath, $siteName, $uri);
    }

    /**
     * Get the path to the static file.
     */
    protected function getStaticPath(string $sitePath): string
    {
        $parts = parse_url($_SERVER['REQUEST_URI']);
        $query = $parts['query'] ?? '';

        return $sitePath.'/public/static'.$parts['path'].'_'.$query.'.html';
    }
}
