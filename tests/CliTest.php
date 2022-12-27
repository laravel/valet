<?php

/**
 * @requires PHP >= 8.0
 */
class CliTest extends BaseApplicationTestCase
{
    public function test_park_command()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'park', 'path' => './tests/output']);

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString(
            "The [./tests/output] directory has been added to Valet's paths.",
            $tester->getDisplay()
        );

        $paths = data_get(Configuration::read(), 'paths');

        $this->assertEquals(1, count($paths));
        $this->assertEquals('./tests/output', reset($paths));
    }

    public function test_status_command()
    {
        // @todo: Mock Status so it doesn't do anything real. also have it output various
        // .      states and test the output here
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'status']);

        $tester->assertCommandIsSuccessful();
    }
}
