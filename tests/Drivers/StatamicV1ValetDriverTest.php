<?php

use Valet\Drivers\Specific\StatamicV1ValetDriver;

class StatamicV1ValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_statamicv1_projects()
    {
        $driver = new StatamicV1ValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('statamicv1'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_statamicv1_projects()
    {
        $driver = new StatamicV1ValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_gets_front_controller()
    {
        $driver = new StatamicV1ValetDriver();

        $projectPath = $this->projectDir('statamicv1');
        $this->assertEquals($projectPath.'/index.php', $driver->frontControllerPath($projectPath, 'my-site', '/'));
    }
}
