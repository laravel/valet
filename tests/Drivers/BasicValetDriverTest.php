<?php

use Valet\Drivers\BasicValetDriver;

class BasicValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_anything()
    {
        $driver = new BasicValetDriver();

        foreach ($this->projects() as $projectDir) {
            $this->assertTrue($driver->serves($projectDir, 'my-site', '/'));
        }
    }

    public function test_it_serves_php_files_from_root()
    {
        $projectPath = $this->projectDir('basic-no-public');
        $driver = new BasicValetDriver();

        $this->assertEquals(
            $projectPath.'/file-in-root.php',
            $driver->frontControllerPath($projectPath, 'my-site', '/file-in-root.php')
        );
    }

    public function test_it_serves_directory_with_index_php()
    {
        $projectPath = $this->projectDir('basic-no-public');
        $driver = new BasicValetDriver();

        $this->assertEquals(
            $projectPath.'/about/index.php',
            $driver->frontControllerPath($projectPath, 'my-site', '/about')
        );
    }

    public function test_it_routes_to_index_if_404()
    {
        $projectPath = $this->projectDir('basic-no-public');
        $driver = new BasicValetDriver();

        $this->assertEquals(
            $projectPath.'/index.php',
            $driver->frontControllerPath($projectPath, 'my-site', '/not-a-real-url')
        );
    }

    public function test_it_serves_directory_with_index_html()
    {
        $projectPath = $this->projectDir('basic-no-public');
        $driver = new BasicValetDriver();

        $this->assertEquals(
            $projectPath.'/team/index.html',
            $driver->isStaticFile($projectPath, 'my-site', '/team')
        );
    }

    public function test_it_serves_static_files()
    {
        $projectPath = $this->projectDir('basic-no-public');
        $driver = new BasicValetDriver();

        $this->assertEquals(
            $projectPath.'/assets/document.txt',
            $driver->isStaticFile($projectPath, 'my-site', '/assets/document.txt')
        );
    }
}
