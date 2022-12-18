<?php

use Valet\Drivers\Specific\SymfonyValetDriver;

class SymfonyValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_symfony_projects()
    {
        $driver = new SymfonyValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('symfony'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_symfony_projects()
    {
        $driver = new SymfonyValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_gets_front_controller()
    {
        $driver = new SymfonyValetDriver();

        $projectPath = $this->projectDir('symfony');
        $this->assertEquals($projectPath.'/web/app.php', $driver->frontControllerPath($projectPath, 'my-site', '/'));
    }
}
