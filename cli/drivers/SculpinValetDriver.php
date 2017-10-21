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
        return is_dir($sitePath.'/source') && is_dir($sitePath.'/output_dev');
    }

    private function isLegacySculpinProject($sitePath)
    {
        return is_dir($sitePath.'/.sculpin');
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
