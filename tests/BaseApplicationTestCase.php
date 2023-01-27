<?php

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;
use Valet\Os\Mac\Brew;
use Valet\Os\Os;
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
        Container::setInstance($container = new Container); // Reset app container from previous tests
        $container->instance('os', $os = Os::assign());
        // $container->instance(Installer::class, $os->installer());
        $container->instance(Installer::class, resolve(Brew::class));


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

        return [$app, $tester];
    }
}
