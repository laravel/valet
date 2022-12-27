<?php

use function PHPUnit\Framework\assertStringEndsWith;

/**
 * @requires PHP >= 8.0
 */
class CliTest extends BaseApplicationTestCase
{
    public function test_tld_command_reads_tld()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'tld']);

        $tester->assertCommandIsSuccessful();

        $this->assertEquals('test', trim($tester->getDisplay()));
    }

    public function test_tld_command_sets_tld()
    {
        $this->markTestIncomplete();

        // [$app, $tester] = $this->appAndTester();

        // @todo: Mock DnsMasq, Site, PhpFpm, Nginx, Configuration...
        // $tester->setInputs(['Y']);
        // $tester->run(['command' => 'tld', 'tld' => 'buzz']);
        // $tester->assertCommandIsSuccessful();
    }

    public function test_loopback_command_reads_loopback()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'loopback']);
        $tester->assertCommandIsSuccessful();

        $this->assertEquals('127.0.0.1', trim($tester->getDisplay()));
    }

    public function test_loopback_command_sets_loopback()
    {
        $this->markTestIncomplete();

        // @todo: Mock everything...
        // [$app, $tester] = $this->appAndTester();

        // $tester->run(['command' => 'loopback', 'loopback' => '127.0.0.9']);
        // $tester->assertCommandIsSuccessful();
    }

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

    public function test_parked_command()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'parked']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringNotContainsString('test', $tester->getDisplay());

        Configuration::addPath(__DIR__ . '/fixtures/Parked/Sites');

        $tester->run(['command' => 'parked']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('my-best-site', $tester->getDisplay());
    }
}
