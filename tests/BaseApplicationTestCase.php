<?php

use Symfony\Component\Console\Tester\ApplicationTester;

class BaseApplicationTestCase extends Yoast\PHPUnitPolyfills\TestCases\TestCase
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

    public function appAndTester()
    {
        $app = require __DIR__.'/../cli/app.php';
        $app->setAutoExit(false);
        $tester = new ApplicationTester($app);

        return [$app, $tester];
    }
}
