<?php

use Valet\CommandLine;
use Valet\PackageManagers\Apt;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class AptTest extends TestCase
{
    public function setUp()
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container);
    }


    public function tearDown()
    {
        Mockery::close();
    }


    public function test_apt_can_be_resolved_from_container()
    {
        $this->assertInstanceOf(Apt::class, resolve(Apt::class));
    }


    public function test_installed_returns_true_when_given_formula_is_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()
            ->with("dpkg -l php7.0-cli | grep '^ii' | sed 's_  _\\t_g' | cut -f 2")
            ->andReturn('php7.0-cli');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(Apt::class)->installed('php7.0-cli'));
    }


    public function test_installed_returns_false_when_given_formula_is_not_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()
            ->with("dpkg -l php7.0-cli | grep '^ii' | sed 's_  _\\t_g' | cut -f 2")
            ->andReturn('');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Apt::class)->installed('php7.0-cli'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()
            ->with("dpkg -l php7.0-cli | grep '^ii' | sed 's_  _\\t_g' | cut -f 2")
            ->andReturn('php7.0-mcrypt');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Apt::class)->installed('php7.0-cli'));
    }


    public function test_get_php_version_indicates_installed_php_version()
    {
        $apt = Mockery::mock(Apt::class.'[getPHPVersion]', [new CommandLine]);
        $apt->shouldReceive('getPHPVersion')->once()->andReturn('7.1');
        $this->assertSame($apt->getPHPVersion(), '7.1');

        $apt = Mockery::mock(Apt::class.'[getPHPVersion]', [new CommandLine]);
        $apt->shouldReceive('getPHPVersion')->once()->andReturn('7.0');
        $this->assertNotSame($apt->getPHPVersion(), '7.1');
    }


    public function test_install_or_fail_will_install_packages()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('apt-get install -y dnsmasq', Mockery::type('Closure'));
        swap(CommandLine::class, $cli);
        resolve(Apt::class)->installOrFail('dnsmasq');
    }


    /**
     * @expectedException DomainException
     */
    public function test_install_or_fail_throws_exception_on_failure()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->andReturnUsing(function ($command, $onError) {
            $onError(1, 'test error ouput');
        });
        swap(CommandLine::class, $cli);
        resolve(Apt::class)->installOrFail('dnsmasq');
    }
}
