<?php

use Valet\Drivers\BasicWithPublicValetDriver;

class BasicWithPublicValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_anything_with_public()
    {
        $driver = new BasicWithPublicValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_from_not_public()
    {
        $driver = new BasicWithPublicValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('basic-no-public'), 'my-site', '/'));
    }

    public function test_it_serves_php_files_from_public()
    {
        $projectPath = $this->projectDir('public-with-index-non-laravel');
        $driver = new BasicWithPublicValetDriver();

        $this->assertEquals(
            $projectPath.'/public/file-in-public.php',
            $driver->frontControllerPath($projectPath, 'my-site', '/file-in-public.php')
        );
    }

    public function test_it_doesnt_serve_php_files_from_root()
    {
        $projectPath = $this->projectDir('public-with-index-non-laravel');
        $driver = new BasicWithPublicValetDriver();

        $this->assertEquals(
            $projectPath.'/public/index.php',
            $driver->frontControllerPath($projectPath, 'my-site', '/file-in-root.php')
        );
    }

    public function test_it_serves_directory_with_index_php()
    {
        $projectPath = $this->projectDir('public-with-index-non-laravel');
        $driver = new BasicWithPublicValetDriver();

        $this->assertEquals(
            $projectPath.'/public/about/index.php',
            $driver->frontControllerPath($projectPath, 'my-site', '/about')
        );
    }

    public function test_it_route_to_public_index_if_404()
    {
        $projectPath = $this->projectDir('public-with-index-non-laravel');
        $driver = new BasicWithPublicValetDriver();

        $this->assertEquals(
            $projectPath.'/public/index.php',
            $driver->frontControllerPath($projectPath, 'my-site', '/not-a-real-url')
        );
    }

    public function test_it_serves_directory_with_index_html()
    {
        $projectPath = $this->projectDir('public-with-index-non-laravel');
        $driver = new BasicWithPublicValetDriver();

        $this->assertEquals(
            $projectPath.'/public/team/index.html',
            $driver->isStaticFile($projectPath, 'my-site', '/team')
        );
    }

    public function test_it_serves_static_files()
    {
        $projectPath = $this->projectDir('public-with-index-non-laravel');
        $driver = new BasicWithPublicValetDriver();

        $this->assertEquals(
            $projectPath.'/public/assets/document.txt',
            $driver->isStaticFile($projectPath, 'my-site', '/assets/document.txt')
        );
    }
}
