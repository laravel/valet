<?php

namespace Valet;

use Exception;
use DomainException;
use Symfony\Component\Process\Process;

class PhpFpm
{
    var $brew, $cli, $files;

    var $taps = [
        'homebrew/homebrew-core'
    ];

    /**
     * Create a new PHP FPM class instance.
     *
     * @param  Brew  $brew
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    function __construct(Brew $brew, CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->brew = $brew;
        $this->files = $files;
    }

    /**
     * Install and configure DnsMasq.
     *
     * @return void
     */
    function install()
    {
        if (! $this->brew->hasInstalledPhp()) {
            $this->brew->ensureInstalled('php71', [], $this->taps);
        }

        $this->files->ensureDirExists('/usr/local/var/log', user());

        $this->updateConfiguration();

        $this->restart();
    }

    /**
     * Update the PHP FPM configuration.
     *
     * @return void
     */
    function updateConfiguration()
    {
        info('Updating PHP configuration...');

        $contents = $this->files->get($this->fpmConfigPath());

        $contents = preg_replace('/^user = .+$/m', 'user = '.user(), $contents);
        $contents = preg_replace('/^group = .+$/m', 'group = staff', $contents);
        $contents = preg_replace('/^listen = .+$/m', 'listen = '.VALET_HOME_PATH.'/valet.sock', $contents);
        $contents = preg_replace('/^;?listen\.owner = .+$/m', 'listen.owner = '.user(), $contents);
        $contents = preg_replace('/^;?listen\.group = .+$/m', 'listen.group = staff', $contents);
        $contents = preg_replace('/^;?listen\.mode = .+$/m', 'listen.mode = 0777', $contents);

        $this->files->put($this->fpmConfigPath(), $contents);


        $contents = $this->files->get(__DIR__.'/../stubs/php-memory-limits.ini');

        $destFile = dirname($this->fpmConfigPath());
        $destFile = str_replace('/php-fpm.d', '', $destFile);
        $destFile .= '/conf.d/php-memory-limits.ini';
        $this->files->ensureDirExists(dirname($destFile), user());

        $this->files->putAsUser($destFile, $contents);
    }

    /**
     * Restart the PHP FPM process.
     *
     * @return void
     */
    function restart()
    {
        $this->brew->restartLinkedPhp();
    }

    /**
     * Stop the PHP FPM process.
     *
     * @return void
     */
    function stop()
    {
        call_user_func_array(
            [$this->brew, 'stopService'],
            Brew::SUPPORTED_PHP_VERSIONS
        );
    }

    /**
     * Get the path to the FPM configuration file for the current PHP version.
     *
     * @return string
     */
    function fpmConfigPath()
    {
        $version = $this->brew->linkedPhp();

        $versionNormalized = preg_replace(
            '/php@?(\d)\.?(\d)/',
            '$1.$2',
            $version === 'php' ? Brew::LATEST_PHP_VERSION : $version
        );

        return $versionNormalized === '5.6'
            ? '/usr/local/etc/php/5.6/php-fpm.conf'
            : "/usr/local/etc/php/${versionNormalized}/php-fpm.d/www.conf";
    }
}
