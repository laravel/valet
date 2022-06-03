<?php

use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @requires PHP >= 8.0
 */
class CliTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
{
    public function prepTestConfig()
    {
        require_once __DIR__.'/../cli/includes/helpers.php';

        if (Filesystem::isDir(VALET_HOME_PATH)) {
            Filesystem::rmDirAndContents(VALET_HOME_PATH);
        }

        Configuration::createConfigurationDirectory();
        Configuration::writeBaseConfiguration();
    }

    public function testParkCommand()
    {
        if (! getenv('CI')) {
            $this->markTestSkipped('This test is only run on CI.');
        }

        $this->prepTestConfig();

        $application = require __DIR__.'/../cli/app.php';
        $application->setAutoExit(false);
        $tester = new ApplicationTester($application);

        $tester->run(['command' => 'park', 'path' => './tests/output']);

        $this->assertStringContainsString(
            "The [./tests/output] directory has been added to Valet's paths.",
            $tester->getDisplay()
        );
    }
}
