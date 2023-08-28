<?php

use Valet\Drivers\Specific\StatamicV2ValetDriver;

class StatamicV2ValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_statamic_projects()
    {
        $driver = new StatamicV2ValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('statamicv2'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_statamic_projects()
    {
        $driver = new StatamicV2ValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_gets_front_controller()
    {
        $driver = new StatamicV2ValetDriver();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/about/';

        $projectPath = $this->projectDir('statamicv2');
        $this->assertEquals($projectPath.'/index.php', $driver->frontControllerPath($projectPath, 'my-site', '/'));
    }
}
