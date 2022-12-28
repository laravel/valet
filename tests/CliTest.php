<?php

use Valet\Brew;
use Valet\CommandLine;
use Valet\Filesystem;
use function Valet\swap;

/**
 * @requires PHP >= 8.0
 */
class CliTest extends BaseApplicationTestCase
{
    public function tearDown(): void
    {
        Mockery::close();
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

    public function test_status_command_succeeding()
    {
        [$app, $tester] = $this->appAndTester();

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('hasInstalledPhp')->andReturn(true);
        $brew->shouldReceive('installed')->twice()->andReturn(true);

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->andReturn(true);

        $files = Mockery::mock(Filesystem::class.'[exists]');
        $files->shouldReceive('exists')->once()->andReturn(true);

        swap(Brew::class, $brew);
        swap(CommandLine::class, $cli);
        swap(Filesystem::class, $files);

        $tester->run(['command' => 'status']);

        $tester->assertCommandIsSuccessful();
        $this->assertStringNotContainsString('False', $tester->getDisplay());
    }

    public function test_status_command_failing()
    {
        [$app, $tester] = $this->appAndTester();

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('hasInstalledPhp')->andReturn(true);
        $brew->shouldReceive('installed')->twice()->andReturn(true);

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->andReturn(true);

        $files = Mockery::mock(Filesystem::class . '[exists]');
        $files->shouldReceive('exists')->once()->andReturn(false);

        swap(Brew::class, $brew);
        swap(CommandLine::class, $cli);
        swap(Filesystem::class, $files);

        $tester->run(['command' => 'status']);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('False', $tester->getDisplay());
    }
}
