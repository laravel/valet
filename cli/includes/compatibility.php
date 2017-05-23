<?php

/**
 * Check the system's compatibility with Valet.
 */
$inTestingEnvironment = strpos($_SERVER['SCRIPT_NAME'], 'phpunit') !== false;

if (PHP_OS !== 'Darwin' && ! $inTestingEnvironment) {
    echo 'Valet only supports the Mac operating system.'.PHP_EOL;

    exit(1);
}

if (version_compare(PHP_VERSION, '5.6.0', '<')) {
    echo "Valet requires PHP 5.6 or later.";

    exit(1);
}

if (exec('which brew') == '' && ! $inTestingEnvironment) {
    echo 'Valet requires Homebrew to be installed on your Mac.';

    exit(1);
}
