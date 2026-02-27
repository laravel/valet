<?php

// Allow bypassing these checks if using Valet in a non-CLI app
if (php_sapi_name() !== 'cli') {
    return;
}

/**
 * Check the system's compatibility with Valet.
 */
$inTestingEnvironment = strpos($_SERVER['SCRIPT_NAME'], 'phpunit') !== false;

 $supportedOperatingSystems = ['Darwin', 'Linux'];

 if (! in_array(PHP_OS, $supportedOperatingSystems, true) && ! $inTestingEnvironment) {
    echo 'This fork of Valet only supports macOS and Linux (detected: '.PHP_OS.').'.PHP_EOL;

    exit(1);
}

if (version_compare(PHP_VERSION, '8.0', '<')) {
    echo 'Valet requires PHP 8.0 or later.';

    exit(1);
}

// Detect Homebrew differently on macOS vs Linux
if (PHP_OS === 'Darwin') {
    // Preserve original macOS behaviour
    if (exec('which brew') == '' && ! $inTestingEnvironment) {
        echo 'Valet requires Homebrew to be installed on your Mac.';

        exit(1);
    }
} else {
    // Linux / other supported OS: support Homebrew/Linuxbrew in common locations
    $brewPath = trim(exec('command -v brew 2>/dev/null'));

    if ($brewPath === '') {
        $commonBrewPaths = [
            '/opt/homebrew/bin/brew',
            '/usr/local/bin/brew',
            '/home/linuxbrew/.linuxbrew/bin/brew',
        ];

        foreach ($commonBrewPaths as $path) {
            if (file_exists($path)) {
                $brewPath = $path;
                break;
            }
        }
    }

    if ($brewPath === '' && ! $inTestingEnvironment) {
        echo 'Valet requires Homebrew (brew) to be installed and available in your PATH.';

        exit(1);
    }
}
