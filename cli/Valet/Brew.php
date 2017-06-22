<?php

namespace Valet;

use Exception;
use DomainException;

class Brew
{
    var $cli, $files;

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
        return in_array($formula, explode(PHP_EOL, $this->cli->runAsUser('brew list | grep '.$formula)));
    }

    /**
     * Determine if a compatible PHP version is Homebrewed.
     *
     * @return bool
     */
    function hasInstalledPhp()
    {
        return $this->supportedPhpVersions()->contains(function ($version) {
            return $this->installed($version);
        });
    }

    /**
     * Get a list of supported PHP versions
     *
     * @return \Illuminate\Support\Collection
     */
    function supportedPhpVersions()
    {
        return collect(['php72', 'php71', 'php70', 'php56']);
    }

    /**
     * Determine if a compatible nginx version is Homebrewed.
     *
     * @return bool
     */
    function hasInstalledNginx()
    {
        return $this->installed('nginx')
            || $this->installed('nginx-full');
    }

    /**
     * Return name of the nginx service installed via Homebrewed.
     *
     * @return string
     */
    function nginxServiceName()
    {
        return $this->installed('nginx-full') ? 'nginx-full' : 'nginx';
    }

    /**
     * Ensure that the given formula is installed.
     *
     * @param  string  $formula
     * @param  array  $options
     * @param  array  $taps
     * @return void
     */
    function ensureInstalled($formula, $options = [], $taps = [])
    {
        if (! $this->installed($formula)) {
            $this->installOrFail($formula, $options, $taps);
        }
    }

    /**
     * Install the given formula and throw an exception on failure.
     *
     * @param  string  $formula
     * @param  array  $options
     * @param  array  $taps
     * @return void
     */
    function installOrFail($formula, $options = [], $taps = [])
    {
        info("Installing {$formula}...");

        if (count($taps) > 0) {
            $this->tap($taps);
        }

        output('<info>['.$formula.'] is not installed, installing it now via Brew...</info> 🍻');

        $this->cli->runAsUser(trim('brew install '.$formula.' '.implode(' ', $options)), function ($exitCode, $errorOutput) use ($formula) {
            output($errorOutput);

            throw new DomainException('Brew was unable to install ['.$formula.'].');
        });
    }

    /**
     * Tap the given formulas.
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
            if ($this->installed($service)) {
                info("Restarting {$service}...");

                $this->cli->quietly('sudo brew services stop '.$service);
                $this->cli->quietly('sudo brew services start '.$service);
            }
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
            if ($this->installed($service)) {
                info("Stopping {$service}...");

                $this->cli->quietly('sudo brew services stop '.$service);
            }
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

        return $this->supportedPhpVersions()->first(function ($version) use ($resolvedPath) {
            return strpos($resolvedPath, $version) !== false;
        }, function () {
            throw new DomainException("Unable to determine linked PHP.");
        });
    }

    /**
     * Restart the linked PHP-FPM Homebrew service.
     *
     * @return void
     */
    function restartLinkedPhp()
    {
        $this->restartService($this->linkedPhp());
    }
}
