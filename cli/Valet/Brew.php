<?php

namespace Valet;

use Exception;
use DomainException;

class Brew
{
    var $cli;
    var $files;

    /**
     * Create a new Brew instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Determine if the given formula is installed.
     *
     * @param  string  $formula
     * @return bool
     */
    function installed($formula)
    {
        return in_array($formula, explode(PHP_EOL, $this->cli->run('brew list | grep '.$formula)));
    }

    /**
     * Determine if a compatible PHP version is Homebrewed.
     *
     * @return bool
     */
    function hasInstalledPhp()
    {
        return $this->installed('php70') || $this->installed('php56');
    }

    /**
     * Ensure that the given formula is installed.
     *
     * @param  string  $formula
     * @param  array  $taps
     * @return void
     */
    function ensureInstalled($formula, array $taps = [])
    {
        if (! $this->installed($formula)) {
            $this->installOrFail($formula, $taps);
        }
    }

    /**
     * Install the given formula and throw an exception on failure.
     *
     * @param  string  $formula
     * @param  array  $taps
     * @return void
     */
    function installOrFail($formula, array $taps = [])
    {
        if (count($taps) > 0) {
            $this->tap($taps);
        }

        output('<info>['.$formula.'] is not installed, installing it now via Brew...</info> 🍻');

        $this->cli->run('brew install '.$formula, function ($errorOutput) use ($formula) {
            output($errorOutput);

            throw new DomainException('Brew was unable to install ['.$formula.'].');
        });
    }

    /**
     * Tag the given formulas.
     *
     * @param  dynamic[string]  $formula
     * @return void
     */
    function tap($formulas)
    {
        $formulas = is_array($formulas) ? $formulas : func_get_args();

        foreach ($formulas as $formula) {
            $this->cli->passthru('sudo -u '.user().' brew tap '.$formula);
        }
    }

    /**
     * Restart the given Homebrew services.
     *
     * @param
     */
    function restartService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $this->cli->quietly('sudo brew services restart '.$service);
        }
    }

    /**
     * Stop the given Homebrew services.
     *
     * @param
     */
    function stopService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $this->cli->quietly('sudo brew services stop '.$service);
        }
    }

    /**
     * Determine which version of PHP is linked in Homebrew.
     *
     * @return string
     */
    function linkedPhp()
    {
        if (! $this->files->isLink('/usr/local/bin/php')) {
            throw new DomainException("Unable to determine linked PHP.");
        }

        $resolvedPath = $this->files->readLink('/usr/local/bin/php');

        if (strpos($resolvedPath, 'php70') !== false) {
            return 'php70';
        } elseif (strpos($resolvedPath, 'php56') !== false) {
            return 'php56';
        } else {
            throw new DomainException("Unable to determine linked PHP.");
        }
    }

    /**
     * Restart the linked PHP-FPM Homebrew service.
     *
     * @return void
     */
    function restartLinkedPhp()
    {
        return $this->restartService($this->linkedPhp());
    }
}
