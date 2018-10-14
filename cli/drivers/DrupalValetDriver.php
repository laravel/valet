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
        $sitePath = $this->addSubdirectory($sitePath);

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
        $sitePath = $this->addSubdirectory($sitePath);

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
        $sitePath = $this->addSubdirectory($sitePath);

        if (!isset($_GET['q']) && !empty($uri) && $uri !== '/') {
          $_GET['q'] = $uri;
        }

        $matches = [];
        if (preg_match('/^\/(.*?)\.php/', $uri, $matches)) {
            $filename = $matches[0];
            if (file_exists($sitePath.$filename) && ! is_dir($sitePath.$filename)) {
                $_SERVER['SCRIPT_FILENAME'] = $sitePath.$filename;
                $_SERVER['SCRIPT_NAME'] = $filename;
                return $sitePath.$filename;
            }
        }

        // Fallback
        $_SERVER['SCRIPT_FILENAME'] = $sitePath.'/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        return $sitePath.'/index.php';
    }

    /**
     * Add any matching subdirectory to the site path.
     */
    public function addSubdirectory($sitePath)
    {
        $paths = array_map(function ($subDir) use ($sitePath) {
            return "$sitePath/$subDir";
        }, $this->possibleSubdirectories());

        $foundPaths = array_filter($paths, function ($path) {
            return file_exists($path);
        });

        // If paths are found, return the first one.
        if (!empty($foundPaths)) {
            return array_shift($foundPaths);
        }

        // If there are no matches, return the original path.
        return $sitePath;
    }

    /**
     * Return an array of possible subdirectories.
     *
     * @return array
     */
    private function possibleSubdirectories()
    {
        return ['docroot', 'public', 'web'];
    }
}
