<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\BasicValetDriver;

class SculpinValetDriver extends BasicValetDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return $this->isModernSculpinProject($sitePath) ||
            $this->isLegacySculpinProject($sitePath);
    }

    private function isModernSculpinProject(string $sitePath): bool
    {
        return is_dir($sitePath.'/source') &&
            is_dir($sitePath.'/output_dev') &&
            $this->composerRequires($sitePath, 'sculpin/sculpin');
    }

    private function isLegacySculpinProject(string $sitePath): bool
    {
        return is_dir($sitePath.'/.sculpin');
    }

    /**
     * Mutate the incoming URI.
     */
    public function mutateUri(string $uri): string
    {
        return rtrim('/output_dev'.$uri, '/');
    }
}
