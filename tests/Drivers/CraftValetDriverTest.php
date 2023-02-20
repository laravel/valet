<?php

use Valet\Drivers\Specific\CraftValetDriver;

class CraftValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_craft_projects()
    {
        $driver = new CraftValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('craft'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_craft_projects()
    {
        $driver = new CraftValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_gets_front_controller()
    {
        $driver = new CraftValetDriver();

        $projectPath = $this->projectDir('craft');
        $this->assertEquals($projectPath.'/public/index.php', $driver->frontControllerPath($projectPath, 'my-site', '/'));
    }
}
