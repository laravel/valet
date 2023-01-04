<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

class BaseApplicationTestCase extends /*Yoast\PHPUnitPolyfills\TestCases\TestCase */ TestCase
{
    use UsesNullWriter;

    public function setUp(): void
    {
        $this->prepTestConfig();
        $this->setNullWriter();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * Prepare a test to run using the full application.
     *
     * @return void
     */
    public function prepTestConfig()
    {
        require_once __DIR__.'/../cli/includes/helpers.php';

        Configuration::createConfigurationDirectory();
        Configuration::createDriversDirectory();
        Configuration::writeBaseConfiguration();
    }

    /**
     * Return an array with two items: the application instance and the ApplicationTester.
     *
     * @return array
     */
    public function appAndTester(): array
    {
        $app = require __DIR__.'/../cli/app.php';
        $app->setAutoExit(false);
        $tester = new ApplicationTester($app);

        return [$app, $tester];
    }
}
