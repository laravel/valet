<?php

use Valet\Drivers\Specific\CakeValetDriver;

class CakeValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_cake_projects()
    {
        $driver = new CakeValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('cake'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_cake_projects()
    {
        $driver = new CakeValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_gets_front_controller()
    {
        $driver = new CakeValetDriver();

        $projectPath = $this->projectDir('cake');
        $this->assertEquals($projectPath.'/webroot/index.php', $driver->frontControllerPath($projectPath, 'my-site', '/'));
    }
}
