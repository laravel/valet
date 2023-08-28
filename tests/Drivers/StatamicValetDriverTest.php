<?php

use Valet\Drivers\Specific\StatamicValetDriver;

class StatamicValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_statamic_projects()
    {
        $driver = new StatamicValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('statamic'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_statamic_projects_with_public_directory()
    {
        $driver = new StatamicValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_laravel_projects()
    {
        $driver = new StatamicValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('laravel'), 'my-site', '/'));
    }

    public function test_it_gets_front_controller()
    {
        $driver = new StatamicValetDriver();

        $projectPath = $this->projectDir('statamic');
        $this->assertEquals($projectPath.'/public/index.php', $driver->frontControllerPath($projectPath, 'my-site', '/'));
    }

    public function test_it_serves_statically_cached_pages()
    {
        $driver = new StatamicValetDriver();

        $projectPath = $this->projectDir('statamic');

        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertEquals($projectPath.'/public/static/test_.html', $driver->frontControllerPath($projectPath, 'my-site', '/test'));

        $_SERVER['REQUEST_URI'] = '/test?foo=bar&baz=qux';
        $this->assertEquals($projectPath.'/public/static/test_foo=bar&baz=qux.html', $driver->frontControllerPath($projectPath, 'my-site', '/test'));
    }
}
