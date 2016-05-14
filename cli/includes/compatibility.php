<?php

/**
 * Check the system's compatibility with Valet.
 */
$inTestingEnvironment = strpos($_SERVER['SCRIPT_NAME'], 'phpunit') !== false;

if (PHP_OS != 'Linux' && ! $inTestingEnvironment) {
    echo 'Valet only supports the Ubuntu operating system.'.PHP_EOL;

    exit(1);
}

if (version_compare(PHP_VERSION, '5.5.9', '<')) {
    echo "Valet requires PHP 5.5.9 or later.";

    exit(1);
}

// if (exec('which brew') != '/usr/local/bin/brew' && ! $inTestingEnvironment) {
//     echo 'Valet requires Brew to be installed on your Mac.';

//     exit(1);
// }
