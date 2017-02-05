<?php

/**
 * Check the system's compatibility with Valet.
 */
$inTestingEnvironment = strpos($_SERVER['SCRIPT_NAME'], 'phpunit') !== false;

if (PHP_OS != 'Linux' && ! $inTestingEnvironment) {
    echo 'Valet only supports Linux.'.PHP_EOL;

    exit(1);
}

if (version_compare(PHP_VERSION, '5.6', '<')) {
    echo "Valet requires PHP 5.6 or later.";

    exit(1);
}
