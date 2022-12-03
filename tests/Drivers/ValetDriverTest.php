<?php

use Valet\Drivers\ValetDriver;

class ValetDriverTest extends BaseDriverTestCase
{
    public function test_it_gets_drivers_in_given_path()
    {
        $output = ValetDriver::driversIn(__DIR__.'/../files/Drivers');

        $this->assertEquals(2, count($output));
        $this->assertContains('Test1ValetDriver', $output);
        $this->assertContains('Test2ValetDriver', $output);
    }

    public function test_it_assigns_drivers_to_given_project()
    {
        $assignedDriver = ValetDriver::assign($this->projectDir('bedrock'), 'my-site', '/');

        $this->assertEquals('Valet\Drivers\BedrockValetDriver', get_class($assignedDriver));
    }

    public function test_it_prioritizes_non_basic_matches()
    {
        $assignedDriver = ValetDriver::assign($this->projectDir('laravel'), 'my-site', '/');

        $this->assertNotEquals('Valet\Drivers\BasicWithPublicValetDriver', get_class($assignedDriver));
        $this->assertNotEquals('Valet\Drivers\BasicValetDriver', get_class($assignedDriver));
    }
}
