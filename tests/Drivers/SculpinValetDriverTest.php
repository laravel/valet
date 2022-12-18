<?php

use Valet\Drivers\Specific\SculpinValetDriver;

class SculpinValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_sculpin_projects()
    {
        $driver = new SculpinValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('sculpin'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_sculpin_projects_with_public_directory()
    {
        $driver = new SculpinValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_mutates_uri()
    {
        $driver = new SculpinValetDriver();

        $this->assertEquals('/output_dev/about', $driver->mutateUri('/about'));
    }
}
