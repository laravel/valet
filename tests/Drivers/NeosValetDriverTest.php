<?php

use Valet\Drivers\Specific\NeosValetDriver;

class NeosValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_neos_projects()
    {
        $driver = new NeosValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('neos'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_neos_projects_with_public_directory()
    {
        $driver = new NeosValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_gets_front_controller()
    {
        $driver = new NeosValetDriver();

        $projectPath = $this->projectDir('neos');
        $this->assertEquals($projectPath.'/Web/index.php', $driver->frontControllerPath($projectPath, 'my-site', '/'));
    }
}
