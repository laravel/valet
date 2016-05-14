<?php

use Valet\Ubuntu;
use Valet\Filesystem;
use Valet\CommandLine;
use Illuminate\Container\Container;

class UbuntuTest extends PHPUnit_Framework_TestCase
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
        $this->assertInstanceOf(Ubuntu::class, resolve(Ubuntu::class));
    }


    public function test_installed_returns_true_when_given_formula_is_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('dpkg -l | grep php7.0 | sed \'s_  _\t_g\' | cut -f 2')->andReturn('php7.0');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(Ubuntu::class)->installed('php7.0'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('dpkg -l | grep php7.0 | sed \'s_  _\t_g\' | cut -f 2')->andReturn('php7.0-mcrypt
php7.0');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(Ubuntu::class)->installed('php7.0'));
    }


    public function test_installed_returns_false_when_given_formula_is_not_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('dpkg -l | grep php7.0 | sed \'s_  _\t_g\' | cut -f 2')->andReturn('');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Ubuntu::class)->installed('php7.0'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('dpkg -l | grep php7.0 | sed \'s_  _\t_g\' | cut -f 2')->andReturn('php7.0-mcrypt');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Ubuntu::class)->installed('php7.0'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('dpkg -l | grep php7.0 | sed \'s_  _\t_g\' | cut -f 2')->andReturn('php7.0-mcrypt
php7.0-something-else
php7');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Ubuntu::class)->installed('php7.0'));
    }


    public function test_has_installed_php_indicates_if_php_is_installed_via_apt()
    {
        $apt = Mockery::mock(Ubuntu::class.'[installed]', [new CommandLine, new Filesystem]);
        $apt->shouldReceive('installed')->once()->with('php7.0')->andReturn(true);
        $apt->shouldReceive('installed')->with('php5.6')->andReturn(true);
        $apt->shouldReceive('installed')->with('php5.5')->andReturn(true);
        $this->assertTrue($apt->hasInstalledPhp());

        $apt = Mockery::mock(Ubuntu::class.'[installed]', [new CommandLine, new Filesystem]);
        $apt->shouldReceive('installed')->once()->with('php7.0')->andReturn(true);
        $apt->shouldReceive('installed')->with('php5.6')->andReturn(false);
        $apt->shouldReceive('installed')->with('php5.5')->andReturn(false);
        $this->assertTrue($apt->hasInstalledPhp());

        $apt = Mockery::mock(Ubuntu::class.'[installed]', [new CommandLine, new Filesystem]);
        $apt->shouldReceive('installed')->once()->with('php7.0')->andReturn(false);
        $apt->shouldReceive('installed')->once()->with('php5.6')->andReturn(false);
        $apt->shouldReceive('installed')->once()->with('php5.5')->andReturn(false);
        $this->assertFalse($apt->hasInstalledPhp());
    }


    public function test_restart_restarts_the_service_using_ubuntu_services()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietly')->once()->with('sudo service dnsmasq restart');
        swap(CommandLine::class, $cli);
        resolve(Ubuntu::class)->restartService('dnsmasq');
    }


    public function test_stop_stops_the_service_using_ubuntu_services()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietly')->once()->with('sudo service dnsmasq stop');
        swap(CommandLine::class, $cli);
        resolve(Ubuntu::class)->stopService('dnsmasq');
    }


    public function test_linked_php_returns_linked_php_formula_name()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isLink')->once()->with('/usr/bin/php')->andReturn(true);
        $files->shouldReceive('readLink')->once()->with('/usr/bin/php')->andReturn('/test/path/php7.0/test');
        swap(Filesystem::class, $files);
        $this->assertEquals('php7.0', resolve(Ubuntu::class)->linkedPhp());

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isLink')->once()->with('/usr/bin/php')->andReturn(true);
        $files->shouldReceive('readLink')->once()->with('/usr/bin/php')->andReturn('/test/path/php5.6/test');
        swap(Filesystem::class, $files);
        $this->assertEquals('php5.6', resolve(Ubuntu::class)->linkedPhp());
    }


    /**
     * @expectedException DomainException
     */
    public function test_linked_php_throws_exception_if_no_php_link()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isLink')->once()->with('/usr/bin/php')->andReturn(false);
        swap(Filesystem::class, $files);
        resolve(Ubuntu::class)->linkedPhp();
    }


    /**
     * @expectedException DomainException
     */
    public function test_linked_php_throws_exception_if_unsupported_php_version_is_linked()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isLink')->once()->with('/usr/bin/php')->andReturn(true);
        $files->shouldReceive('readLink')->once()->with('/usr/bin/php')->andReturn('/test/path/php42/test');
        swap(Filesystem::class, $files);
        resolve(Ubuntu::class)->linkedPhp();
    }


    public function test_install_or_fail_will_install_packages()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('apt-get install dnsmasq', Mockery::type('Closure'));
        swap(CommandLine::class, $cli);
        resolve(Ubuntu::class)->installOrFail('dnsmasq');
    }


    /**
     * @expectedException DomainException
     */
    public function test_install_or_fail_throws_exception_on_failure()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->andReturnUsing(function ($command, $onError) {
            $onError('test error ouput');
        });
        swap(CommandLine::class, $cli);
        resolve(Ubuntu::class)->installOrFail('dnsmasq');
    }
}
