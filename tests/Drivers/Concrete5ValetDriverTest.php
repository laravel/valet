<?php

use Valet\Drivers\Specific\Concrete5ValetDriver;

class Concrete5ValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_concrete5_projects()
    {
        $driver = new Concrete5ValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('concrete5'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_concrete5_projects()
    {
        $driver = new Concrete5ValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_gets_front_controller()
    {
        $driver = new Concrete5ValetDriver();

        $projectPath = $this->projectDir('concrete5');
        $this->assertEquals($projectPath.'/index.php', $driver->frontControllerPath($projectPath, 'my-site', '/'));
    }
}
