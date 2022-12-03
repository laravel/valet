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
}
