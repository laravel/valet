<?php

use Illuminate\Container\Container;
use Valet\CommandLine;
use Valet\Composer;

use function Valet\resolve;
use function Valet\swap;
use function Valet\user;

class ComposerTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
{
    use UsesNullWriter;

    public function set_up()
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container);
        $this->setNullWriter();
    }

    public function tear_down()
    {
        Mockery::close();
    }

    public function test_composer_can_be_resolved_from_container()
    {
        $this->assertInstanceOf(Composer::class, resolve(Composer::class));
    }

    public function test_installed_returns_true_when_given_package_is_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('composer global show --format json -- beyondcode/expose')
            ->andReturn('{"name":"beyondcode/expose"}');
        swap(CommandLine::class, $cli);

        $this->assertTrue(resolve(Composer::class)->installed('beyondcode/expose'));
    }

    public function test_installed_returns_false_when_given_formula_is_not_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('composer global show --format json -- beyondcode/expose')
            ->andReturn("Changed current directory to /Users/mattstauffer/.composer\n\n[InvalidArgumentException]\nPackage beyondcode/expose not found");
        swap(CommandLine::class, $cli);

        $this->assertFalse(resolve(Composer::class)->installed('beyondcode/expose'));
    }

    public function test_install_or_fail_will_install_composer_package()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('composer global require beyondcode/expose', Mockery::type('Closure'));
        swap(CommandLine::class, $cli);

        resolve(Composer::class)->installOrFail('beyondcode/expose');
    }

    public function test_installed_version_returns_null_when_given_package_is_not_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('composer global show --format json -- beyondcode/expose')
            ->andReturn("Changed current directory to /Users/mattstauffer/.composer\n\n[InvalidArgumentException]\nPackage beyondcode/expose not found");
        swap(CommandLine::class, $cli);

        $this->assertNull(resolve(Composer::class)->installedVersion('beyondcode/expose'));
    }

    public function test_installed_version_returns_version_when_package_is_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('composer global show --format json -- beyondcode/expose')
            ->andReturn('{"versions":["1.4.2"]}');
        swap(CommandLine::class, $cli);

        $this->assertEquals('1.4.2', resolve(Composer::class)->installedVersion('beyondcode/expose'));
    }
}
