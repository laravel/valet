<?php

use Valet\Drivers\Specific\KatanaValetDriver;

class KatanaValetDriverTest extends BaseDriverTestCase
{
    public function test_it_serves_katana_projects()
    {
        $driver = new KatanaValetDriver();

        $this->assertTrue($driver->serves($this->projectDir('katana'), 'my-site', '/'));
    }

    public function test_it_doesnt_serve_non_katana_projects_with_public_directory()
    {
        $driver = new KatanaValetDriver();

        $this->assertFalse($driver->serves($this->projectDir('public-with-index-non-laravel'), 'my-site', '/'));
    }

    public function test_it_mutates_uri()
    {
        $driver = new KatanaValetDriver();

        $this->assertEquals('/public/about', $driver->mutateUri('/about'));
    }
}
