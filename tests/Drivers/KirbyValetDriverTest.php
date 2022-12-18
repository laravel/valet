<?php

use Valet\Drivers\Specific\KirbyValetDriver;

class KirbyValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_kirby_projects()
    {
        $driver = new KirbyValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('kirby'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_kirby_projects_with_public_directory()
    {
        $driver = new KirbyValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_gets_front_controller()
    {
        $driver = new KirbyValetDriver();

        $projectPath = $this->projectDir('kirby');
        $this->assertEquals($projectPath.'/index.php', $driver->frontControllerPath($projectPath, 'my-site', '/'));
    }
}
