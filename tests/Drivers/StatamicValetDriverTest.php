<?php

use Valet\Drivers\Specific\StatamicValetDriver;

class StatamicValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_statamic_projects()
    {
        $driver = new StatamicValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('statamic'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_statamic_projects()
    {
        $driver = new StatamicValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_gets_front_controller()
    {
        $driver = new StatamicValetDriver();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/about/';

        $projectPath = $this->projectDir('statamicv1');
        $this->assertEquals($projectPath.'/index.php', $driver->frontControllerPath($projectPath, 'my-site', '/'));
    }
}
