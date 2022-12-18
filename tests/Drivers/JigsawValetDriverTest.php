<?php

use Valet\Drivers\Specific\JigsawValetDriver;

class JigsawValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_jigsaw_projects()
    {
        $driver = new JigsawValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('jigsaw'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_jigsaw_projects_with_public_directory()
    {
        $driver = new JigsawValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_mutates_uri()
    {
        $driver = new JigsawValetDriver();

        $this->assertEquals('/build_local/about', $driver->mutateUri('/about'));
    }
}
