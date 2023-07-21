<?php

namespace Valet;

use Exception;
use Illuminate\Container\Container;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Define constants.
 */
if (! defined('VALET_HOME_PATH')) {
    if (testing()) {
        define('VALET_HOME_PATH', __DIR__.'/../../tests/config/valet');
    } else {
        define('VALET_HOME_PATH', $_SERVER['HOME'].'/.config/valet');
    }
}
if (! defined('VALET_STATIC_PREFIX')) {
    define('VALET_STATIC_PREFIX', '41c270e4-5535-4daa-b23e-c269744c2f45');
}

define('VALET_LOOPBACK', '127.0.0.1');
define('VALET_SERVER_PATH', realpath(__DIR__.'/../../server.php'));

define('BREW_PREFIX', (new CommandLine())->runAsUser('printf $(brew --prefix)'));

define('ISOLATED_PHP_VERSION', 'ISOLATED_PHP_VERSION');

/**
 * Set or get a global console writer.
 */
function writer(OutputInterface $writer = null): OutputInterface|\NullWriter|null
{
    $container = Container::getInstance();

    if (! $writer) {
        if (! $container->bound('writer')) {
            $container->instance('writer', new ConsoleOutput());
        }

        return $container->make('writer');
    }

    $container->instance('writer', $writer);

    return null;
}

/**
 * Output the given text to the console.
 */
function info($output): void
{
    output('<info>'.$output.'</info>');
}

/**
 * Output the given text to the console.
 */
function warning(string $output): void
{
    output('<fg=red>'.$output.'</>');
}

/**
 * Output a table to the console.
 */
function table(array $headers = [], array $rows = []): void
{
    $table = new Table(writer());

    $table->setHeaders($headers)->setRows($rows);

    $table->render();
}

/**
 * Return whether the app is in the testing environment.
 */
function testing(): bool
{
    return strpos($_SERVER['SCRIPT_NAME'], 'phpunit') !== false;
}

/**
 * Output the given text to the console.
 */
function output(?string $output = ''): void
{
    writer()->writeln($output);
}

/**
 * Resolve the given class from the container.
 */
function resolve(string $class): mixed
{
    return Container::getInstance()->make($class);
}

/**
 * Swap the given class implementation in the container.
 */
function swap(string $class, mixed $instance): void
{
    Container::getInstance()->instance($class, $instance);
}

/**
 * Retry the given function N times.
 */
function retry(int $retries, callable $fn, int $sleep = 0): mixed
{
    beginning:
    try {
        return $fn();
    } catch (Exception $e) {
        if (! $retries) {
            throw $e;
        }

        $retries--;

        if ($sleep > 0) {
            usleep($sleep * 1000);
        }

        goto beginning;
    }
}

/**
 * Verify that the script is currently running as "sudo".
 */
function should_be_sudo(): void
{
    if (! isset($_SERVER['SUDO_USER'])) {
        throw new Exception('This command must be run with sudo.');
    }
}

/**
 * Tap the given value.
 */
function tap(mixed $value, callable $callback): mixed
{
    $callback($value);

    return $value;
}

/**
 * Determine if a given string ends with a given substring.
 */
function ends_with(string $haystack, array|string $needles): bool
{
    foreach ((array) $needles as $needle) {
        if (substr($haystack, -strlen($needle)) === (string) $needle) {
            return true;
        }
    }

    return false;
}

/**
 * Determine if a given string starts with a given substring.
 */
function starts_with(string $haystack, array|string $needles): bool
{
    foreach ((array) $needles as $needle) {
        if ((string) $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0) {
            return true;
        }
    }

    return false;
}

/**
 * Get the user.
 */
function user(): string
{
    if (! isset($_SERVER['SUDO_USER'])) {
        return $_SERVER['USER'];
    }

    return $_SERVER['SUDO_USER'];
}
