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

    private function isModernSculpinProject($sitePath): bool
    {
        return is_dir($sitePath.'/source') &&
            is_dir($sitePath.'/output_dev') &&
            $this->composerRequiresSculpin($sitePath);
    }

    private function isLegacySculpinProject($sitePath): bool
    {
        return is_dir($sitePath.'/.sculpin');
    }

    private function composerRequiresSculpin($sitePath): bool
    {
        if (! file_exists($sitePath.'/composer.json')) {
            return false;
        }

        $composer_json_source = file_get_contents($sitePath.'/composer.json');
        $composer_json = json_decode($composer_json_source, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return isset($composer_json['require']['sculpin/sculpin']);
    }

    /**
     * Mutate the incoming URI.
     */
    public function mutateUri(string $uri): string
    {
        return rtrim('/output_dev'.$uri, '/');
    }
}
