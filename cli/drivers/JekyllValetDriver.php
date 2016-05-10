<?php

class JekyllValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return void
     */
    public function serves($sitePath, $siteName, $uri)
    {
        //Check for several default Jekyll folders and files.
        return 
            is_dir($sitePath.'/'.$this->getServingFolderName())
            && file_exists($sitePath.'/_config.yml')
            && file_exists($sitePath.'/'.$this->getServingFileName());
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        if ( file_exists( $staticPath = $sitePath.'/'.$this->getServingFolderName().$uri ) && ! is_dir($staticPath) ) {
            //It's a static file.
            return $staticPath;
        } elseif ( file_exists( $fauxPath = $staticPath.'/'.$this->getServingFileName() ) ) {
            //Mimic front controller action for all sub directories.
            return $fauxPath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        return $sitePath.'/'.$this->getServingFolderName().'/'.$this->getServingFileName();
    }

    private function getServingFolderName()
    {
        return '_site';
    }

    private function getServingFileName()
    {
        return 'index.html';
    }
}