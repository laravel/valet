<?php

/**
 * Check the system's compatibility with Valet.
 */
$inTestingEnvironment = strpos($_SERVER['SCRIPT_NAME'], 'phpunit') !== false;

if (PHP_OS != 'Darwin' && ! $inTestingEnvironment) {
    echo 'Valet only supports the Mac operating system.'.PHP_EOL;

    exit(1);
}

if (version_compare(PHP_VERSION, '7.0.1', '<')) {
    echo "Valet requires PHP 7.0.1 or later.";

    exit(1);
}

if (exec('which brew') != '/usr/local/bin/brew' && ! $inTestingEnvironment) {
    echo 'Valet requires Brew to be installed on your Mac.';

    exit(1);
}
