<?php

class Magento1ValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return boolean
     */
    public function serves($sitePath, $siteName, $uri)
    {
        return file_exists($sitePath . '/mage') && file_exists($sitePath . '/index.php');
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        if (file_exists($staticFilePath = $sitePath . $uri)) {
        	return $staticFilePath;
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
	    return $sitePath.'/index.php';
	}
}
