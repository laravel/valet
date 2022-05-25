<?php

use Symfony\Component\Console\Tester\ApplicationTester;

class CliTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
{
    public function testParkCommand()
    {
        if (! getenv('CI')) {
            $this->markTestSkipped('This test is only run on CI.');
        }

        $application = require_once __DIR__.'/../cli/app.php';
        $application->setAutoExit(false);
        $tester = new ApplicationTester($application);

        $tester->run(['command' => 'park', 'path' => './tests/output']);

        $this->assertStringContainsString(
            "The [./tests/output] directory has been added to Valet's paths.",
            $tester->getDisplay()
        );
    }
}
