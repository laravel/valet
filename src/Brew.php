<?php

namespace Valet;

use Exception;

class Brew
{
    /**
     * Determine if the given formula is installed.
     *
     * @param  string  $formula
     * @return bool
     */
    public static function installed($formula)
    {
        return in_array($formula, explode(PHP_EOL, run('brew list | grep '.$formula)));
    }

    /**
     * Determine if a compatible PHP version is Homebrewed.
     *
     * @return bool
     */
    public static function hasInstalledPhp()
    {
        return static::installed('php70') || static::installed('php56');
    }

    /**
     * Tag the given formulas.
     *
     * @param  dynamic[string]  $formula
     * @return void
     */
    public static function tap($formulas)
    {
        $formulas = is_array($formulas) ? $formulas : func_get_args();

        foreach ($formulas as $formula) {
            passthru('sudo -u '.$_SERVER['SUDO_USER'].' brew tap '.$formula);
        }
    }

    /**
     * Restart the given Homebrew services.
     *
     * @param
     */
    public static function restartService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            quietly('sudo brew services restart '.$service);
        }
    }

    /**
     * Stop the given Homebrew services.
     *
     * @param
     */
    public static function stopService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            quietly('sudo brew services stop '.$service);
        }
    }

    /**
     * Determine which version of PHP is linked in Homebrew.
     *
     * @return string
     */
    public static function linkedPhp()
    {
        if (! is_link('/usr/local/bin/php')) {
            throw new Exception("Unable to determine linked PHP.");
        }

        $resolvedPath = readlink('/usr/local/bin/php');

        if (strpos($resolvedPath, 'php70') !== false) {
            return 'php70';
        } elseif (strpos($resolvedPath, 'php56') !== false) {
            return 'php56';
        } else {
            throw new Exception("Unable to determine linked PHP.");
        }
    }

    /**
     * Restart the linked PHP-FPM Homebrew service.
     *
     * @return void
     */
    public static function restartLinkedPhp()
    {
        return static::restartService(static::linkedPhp());
    }
}
