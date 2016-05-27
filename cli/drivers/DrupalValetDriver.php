<?php

class DrupalValetDriver extends BasicValetDriver
{
  /**
   * Determine if the driver serves the request.
   *
   * @param  string $sitePath
   * @param  string $siteName
   * @param  string $uri
   * @return bool
   */
  public function serves($sitePath, $siteName, $uri)
  {
    return file_exists($sitePath . '/core/misc/drupal.js') || file_exists($sitePath . '/misc/drupal.js');
  }

  /**
   * Get the fully resolved path to the application's front controller.
   *
   * @param  string $sitePath
   * @param  string $siteName
   * @param  string $uri
   * @return string
   */
  public function frontControllerPath($sitePath, $siteName, $uri)
  {
    $_SERVER['PHP_SELF'] = $uri;

    return $sitePath . '/index.php';
  }
}
