<?php

class Yii2ValetDriver extends ValetDriver
{
    /**
    * Determine if the driver serves the request.
    *
    * @Param string $sitePath
    * @Param string $siteName
    * @Param string $uri
    * @return bool
    */
    public function serves($sitePath, $siteName, $uri)
    {
        if (file_exists($sitePath.'/yii')) {
            return true;
        }

        return false;
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
        if ($uri != '/backend/' && (file_exists($staticFilePath = $sitePath.'/frontend/web'.$uri) || file_exists($staticFilePath = $sitePath.str_replace('/backend/', '/backend/web/', $uri)))) {
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
        //Frontcontroller for Yii frontend
        $frontControllerPath = '/frontend/web/index.php';
        
        //Setting for Yii
        $_SERVER['PHP_SELF'] = '/index.php';

        //If Yii backend
        if (strstr($uri, '/backend/')) {
            //Frontcontroller for Yii frontend
            $frontControllerPath = '/backend/web/index.php';
            //Setting for Yii
            $_SERVER['PHP_SELF'] = '/backend/index.php';
        }

        //Setting for Yii
        $_SERVER['SCRIPT_FILENAME'] = $sitePath.$frontControllerPath;
        
        return $sitePath.$frontControllerPath;
    }
}
