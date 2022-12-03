<?php

use Valet\Drivers\LaravelValetDriver;

class LaravelValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_laravel_projects()
    {
        $driver = new LaravelValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('laravel'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_laravel_projects_with_public_directory()
    {
        $driver = new LaravelValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_gets_front_controller()
    {
        $driver = new LaravelValetDriver();

        $projectPath = $this->projectDir('laravel');
        $this->assertEquals($projectPath.'/public/index.php', $driver->frontControllerPath($projectPath, 'my-site', '/'));
    }
}
