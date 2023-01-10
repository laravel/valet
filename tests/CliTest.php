<?php

use function Valet\swap;

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

    public function test_which_pecl_command()
    {
        [, $tester] = $this->appAndTester();

        $site = Mockery::mock(\Valet\Site::class);
        $site->shouldReceive('getPhpVersion')
            ->with(null)
            ->andReturn('php@8.2');
        swap(\Valet\Site::class, $site);

        $brew = Mockery::mock(\Valet\Brew::class);
        $brew->shouldReceive('getPeclExecutablePath')
            ->with('php@8.2')
            ->andReturn('/usr/local/opt/php@8.2/bin/pecl');
        swap(\Valet\Brew::class, $brew);

        $tester->run(['command' => 'which-pecl']);

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString(
            "/usr/local/opt/php@8.2/bin/pecl",
            $tester->getDisplay()
        );
    }

    public function test_which_pecl_command_with_site()
    {
        [, $tester] = $this->appAndTester();

        $site = Mockery::mock(\Valet\Site::class);
        $site->shouldReceive('getPhpVersion')
            ->with('test')
            ->andReturn('php@8.1');
        swap(\Valet\Site::class, $site);

        $brew = Mockery::mock(\Valet\Brew::class);
        $brew->shouldReceive('getPeclExecutablePath')
            ->with('php@8.1')
            ->andReturn('/usr/local/opt/php@8.1/bin/pecl');
        swap(\Valet\Brew::class, $brew);

        $tester->run(['command' => 'which-pecl', 'site' => 'test']);

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString(
            "/usr/local/opt/php@8.1/bin/pecl",
            $tester->getDisplay()
        );
    }
}
