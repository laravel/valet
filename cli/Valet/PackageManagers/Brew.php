<?php

namespace Valet\PackageManagers;

use DomainException;
use Valet\CommandLine;
use Valet\Contracts\PackageManager;
use Valet\Filesystem;

class Brew implements PackageManager
{
    var $cli, $files;

    /**
     * Compatibility map for installed package.
     * If any of this is installed the main package
     * will be considered installed.
     *
     * @var array
     */
    var $installedCompatibleMap = [
        'php' => ['php71', 'php70', 'php56', 'php55'],
    ];

    /**
     * Map meta-packages to correct name.
     *
     * @var array
     */
    var $installMap = [
        'php' => 'php70',
    ];

    /**
     * Taps needed for specific packages
     *
     * @var array
     */
    var $taps = [
        'php*' => [
            'homebrew/dupes', 'homebrew/versions', 'homebrew/homebrew-php'
        ],
    ];

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
     * Uses compatibility map.
     *
     * @param  string  $formula
     * @return bool
     */
    function installed($formula)
    {
        return collect(collect($this->installedCompatibleMap)->get($formula, [$formula]))->contains(function ($f) {
            return $this->installedCheck($f);
        });
    }

    /**
     * Determine if the given formula is installed.
     *
     * @param  string  $formula
     * @return bool
     */
    function installedCheck($formula)
    {
        return in_array($formula, explode(PHP_EOL, $this->cli->runAsUser('brew list | grep '.$formula)));
    }

    /**
     * Ensure that the given formula is installed.
     *
     * @param  string  $formula
     * @return void
     */
    function ensureInstalled($formula)
    {
        if (! $this->installed($formula)) {
            $formula = $this->installMap[$formula] ?: $formula;

            $this->installOrFail($formula);
        }
    }

    /**
     * Install the given formula and throw an exception on failure.
     *
     * @param  string  $formula
     * @return void
     */
    function installOrFail($formula)
    {
        $taps = $this->getTapsForFormula($formula);

        if (count($taps) > 0) {
            $this->tap($taps);
        }

        output('<info>['.$formula.'] is not installed, installing it now via Brew...</info> 🍻');

        $this->cli->runAsUser(trim('brew install '.$formula), function ($exitCode, $errorOutput) use ($formula) {
            output($errorOutput);

            throw new DomainException('Brew was unable to install ['.$formula.'].');
        });
    }

    /**
     * Determine taps for given formula
     *
     * @param string $formula
     * @return array
     */
    function getTapsForFormula($formula) {
        return collect($this->taps)->first(function ($value, $key) use ($formula) {
            return fnmatch($key, $formula);
        }, []);
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
     * Return full path to etc configuration.
     *
     * @param  string $path
     * @return string
     */
    function etcDir($path = '')
    {
        return '/usr/local/etc' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Return full path to log.
     *
     * @param  string $path
     * @return string
     */
    function logDir($path = '')
    {
        return '/usr/local/var/log' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Return full path to opt.
     *
     * @param  string $path
     * @return string
     */
    function optDir($path = '')
    {
        return '/usr/local/opt' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Configure package manager on valet install.
     *
     * @return void
     */
    function setup()
    {
        $this->createSudoersEntry();
    }

    /**
     * Create the "sudoers.d" entry for running Brew.
     *
     * @return void
     */
    function createSudoersEntry()
    {
        $this->files->ensureDirExists('/etc/sudoers.d');

        $this->files->put('/etc/sudoers.d/brew', 'Cmnd_Alias BREW = /usr/local/bin/brew *
%admin ALL=(root) NOPASSWD: BREW'.PHP_EOL);
    }

    /**
     * Determine if package manager is available on the system.
     *
     * @return bool
     */
    function isAvailable()
    {
        try {
            $output = $this->cli->run('which brew', function ($exitCode, $output) {
                throw new DomainException('Brew not available');
            });

            return $output != '';
        } catch (DomainException $e) {
            return false;
        }
    }
}
