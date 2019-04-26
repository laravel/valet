<?php

class SculpinValetDriver extends BasicValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return bool
     */
    public function serves($sitePath, $siteName, $uri)
    {
        return $this->isModernSculpinProject($sitePath) ||
            $this->isLegacySculpinProject($sitePath);
    }

    private function isModernSculpinProject($sitePath)
    {
        return is_dir($sitePath.'/source') &&
            is_dir($sitePath.'/output_dev') &&
            $this->composerRequiresSculpin($sitePath);
    }

    private function isLegacySculpinProject($sitePath)
    {
        return is_dir($sitePath.'/.sculpin');
    }

    private function composerRequiresSculpin($sitePath)
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
     *
     * @param  string  $uri
     * @return string
     */
    public function mutateUri($uri)
    {
        return rtrim('/output_dev'.$uri, '/');
    }
}
