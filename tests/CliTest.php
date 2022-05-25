<?php

use Symfony\Component\Console\Tester\ApplicationTester;

class CliTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
{
    protected function setUp(): void
    {
        if (! getenv('CI')) {
            $this->markTestSkipped('This test is only run on CI.');
        }
    }

    public function testParkCommand()
    {
        $application = require_once __DIR__.'/../cli/app.php';
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);

        $tester->run(['command' => 'park', 'path' => './tests/output']);
        $tester->assertCommandIsSuccessful();

        $output = $tester->getDisplay();
        $this->assertStringContainsString("The [./tests/output] directory has been added to Valet's paths.", $output);
    }
}
