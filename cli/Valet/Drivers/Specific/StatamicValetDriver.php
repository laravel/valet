<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\LaravelValetDriver;

class StatamicValetDriver extends LaravelValetDriver
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
        $staticPath = $this->getStaticPath($sitePath);

        if ($staticPath && $this->isActualFile($staticPath)) {
            return $staticPath;
        }

        return parent::frontControllerPath($sitePath, $siteName, $uri);
    }

    /**
     * Get the path to the static file.
     */
    private function getStaticPath(string $sitePath)
    {
        if (! $uri = $_SERVER['REQUEST_URI'] ?? null) {
            return;
        }

        $parts = parse_url($uri);
        $query = $parts['query'] ?? '';

        return $sitePath.'/public/static'.$parts['path'].'_'.$query.'.html';
    }
}
