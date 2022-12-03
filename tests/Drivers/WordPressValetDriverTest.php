<?php

use Valet\Drivers\WordPressValetDriver;

class WordPressValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_wordpress_projects()
    {
        $driver = new WordPressValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('wordpress'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_wordpress_projects()
    {
        $driver = new WordPressValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_gets_front_controller()
    {
        $driver = new WordPressValetDriver();

        $_SERVER['HTTP_HOST'] = 'this is set in Valet requests but not phpunit';

        $projectPath = $this->projectDir('wordpress');
        $this->assertEquals($projectPath.'/index.php', $driver->frontControllerPath($projectPath, 'my-site', '/'));
    }
}
