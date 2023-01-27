<?php

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;
use Valet\Os\Installer;
use Valet\Os\Mac;
use Valet\Os\Mac\Brew;
use function Valet\resolve;

class BaseApplicationTestCase extends TestCase
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
     */
    public function prepTestConfig(): void
    {
        require_once __DIR__.'/../cli/includes/helpers.php';
        Container::setInstance(new Container); // Reset app container from previous tests

        if (Filesystem::isDir(VALET_HOME_PATH)) {
            Filesystem::rmDirAndContents(VALET_HOME_PATH);
        }

        Configuration::createConfigurationDirectory();
        Configuration::createDriversDirectory();
        Configuration::createLogDirectory();
        Configuration::createCertificatesDirectory();
        Configuration::writeBaseConfiguration();

        // Keep this file empty, as it's tailed in a test
        Filesystem::touch(VALET_HOME_PATH.'/Log/nginx-error.log');
    }

    /**
     * Return an array with two items: the application instance and the ApplicationTester.
     */
    public function appAndTester(): array
    {
        $app = require __DIR__.'/../cli/app.php';
        $app->setAutoExit(false);
        $tester = new ApplicationTester($app);

        $container = Container::getInstance();
        $container->instance('os', resolve(Mac::class));
        $container->instance(Installer::class, resolve(Brew::class));

        return [$app, $tester];
    }
}
