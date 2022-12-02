<?php

use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @requires PHP >= 8.0
 */
class CliTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
{
    use UsesNullWriter;

    public function setUp(): void
    {
        $this->prepTestConfig();
        $this->setNullWriter();
    }

    public function prepTestConfig()
    {
        require_once __DIR__.'/../cli/includes/helpers.php';

        if (Filesystem::isDir(VALET_HOME_PATH)) {
            Filesystem::rmDirAndContents(VALET_HOME_PATH);
        }

        Configuration::createConfigurationDirectory();
        Configuration::writeBaseConfiguration();
    }

    public function test_park_command()
    {
        if (! getenv('CI')) {
            // $this->markTestSkipped('This test is only run on CI.');
        }

        $application = require __DIR__.'/../cli/app.php';
        $application->setAutoExit(false);
        $tester = new ApplicationTester($application);

        $tester->run(['command' => 'park', 'path' => './tests/output']);

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString(
            "The [./tests/output] directory has been added to Valet's paths.",
            $tester->getDisplay()
        );

        // @todo Test actual output, I presume.
    }
}
