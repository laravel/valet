<?php

class DrupalValetDriver extends ValetDriver
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
      /**
       * /misc/drupal.js = Drupal 7
       * /core/lib/Drupal.php = Drupal 8
       */
      if (file_exists($sitePath.'/misc/drupal.js') ||
          file_exists($sitePath.'/core/lib/Drupal.php')) {
            return true;
        }
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
        if (file_exists($sitePath.$uri) &&
            ! is_dir($sitePath.$uri) &&
            pathinfo($sitePath.$uri)['extension'] != 'php') {
            return $sitePath.$uri;
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
        if (!empty($uri) && $uri !== '/') {
          $_GET['q'] = $uri;
        }

        if (file_exists($sitePath.$uri) && ! is_dir($sitePath.$uri)) {
          chdir(dirname($sitePath.$uri));
          $_SERVER['SCRIPT_FILENAME'] = $sitePath.$uri;
          $_SERVER['SCRIPT_NAME'] = $uri;
          return $sitePath.$uri;
        } elseif (file_exists($frontControllerPath = $sitePath.$uri.'/index.php')) {
          chdir($sitePath.$uri);
          $_SERVER['SCRIPT_FILENAME'] = $sitePath.$uri.'/index.php';
          $_SERVER['SCRIPT_NAME'] = $uri;
          return $frontControllerPath;
        } else {
          chdir($sitePath);
          $_SERVER['SCRIPT_FILENAME'] = $sitePath.'/index.php';
          $_SERVER['SCRIPT_NAME'] = '/index.php';
          return $sitePath.'/index.php';
        }
    }
}
