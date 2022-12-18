<?php

class BaseDriverTestCase extends Yoast\PHPUnitPolyfills\TestCases\TestCase
{
    public function set_up(): void
    {
        $_SERVER['HTTP_HOST'] = 'this is set in Valet requests but not phpunit';
    }

    public function projects(): array
    {
        return Filesystem::scanDir(__DIR__.'/projects');
    }

    public function projectDir(string $name): string
    {
        return __DIR__.'/projects/'.$name;
    }
}
