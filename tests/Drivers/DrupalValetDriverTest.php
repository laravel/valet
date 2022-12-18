<?php

use Valet\Drivers\Specific\DrupalValetDriver;

class DrupalValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_drupal_projects()
    {
        $driver = new DrupalValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('drupal'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_drupal_projects()
    {
        $driver = new DrupalValetDriver();

        $this->assertFalse((bool) $driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_gets_front_controller()
    {
        $driver = new DrupalValetDriver();

        $projectPath = $this->projectDir('drupal');
        $this->assertEquals($projectPath.'/public/index.php', $driver->frontControllerPath($projectPath, 'my-site', '/'));
    }
}
