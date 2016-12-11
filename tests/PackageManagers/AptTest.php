<?php

use Valet\Contracts\PackageManager;
use Valet\Filesystem;
use Valet\CommandLine;
use Illuminate\Container\Container;
use Valet\PackageManagers\Apt;

class AptTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container);

        Container::getInstance()->bind(PackageManager::class, Apt::class);
    }


    public function tearDown()
    {
        Mockery::close();
    }


    public function test_apt_can_be_resolved_from_container()
    {
        $this->assertInstanceOf(Apt::class, resolve(PackageManager::class));
    }


    public function test_apt_is_available()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('which apt', Mockery::type('Closure'))->andReturn('/usr/bin/apt');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(PackageManager::class)->isAvailable());
    }


    public function test_apt_is_not_available()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('which apt', Mockery::type('Closure'))->andReturn('');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(PackageManager::class)->isAvailable());

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('which apt', Mockery::type('Closure'))
            ->andReturnUsing(function ($command, $onError) {
                $onError(1, 'no apt');
            });
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(PackageManager::class)->isAvailable());
    }


    public function test_installed_returns_true_when_given_formula_is_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('dpkg -l php')->andReturn('ii  php 1:7.0+35ubuntu6 all server-side, HTML-embedded scripting language (default)');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(PackageManager::class)->installed('php'));
    }


    public function test_installed_returns_false_when_given_formula_is_not_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('dpkg -l php')->andReturn('dpkg-query: no packages found matching php');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(PackageManager::class)->installed('php'));
    }


    public function test_install_or_fail_will_install_apt_formulas()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('apt-get install -y dnsmasq', Mockery::type('Closure'));
        swap(CommandLine::class, $cli);
        resolve(PackageManager::class)->installOrFail('dnsmasq');
    }


    public function test_etc_dir_is_correct()
    {
        $this->assertEquals('/etc', resolve(PackageManager::class)->etcDir());
        $this->assertEquals('/etc/nginx/nginx.conf', resolve(PackageManager::class)->etcDir('nginx/nginx.conf'));
    }


    public function test_log_dir_is_correct()
    {
        $this->assertEquals('/var/log', resolve(PackageManager::class)->logDir());
        $this->assertEquals('/var/log/nginx/error.log', resolve(PackageManager::class)->logDir('nginx/error.log'));
    }


    public function test_opt_dir_is_correct()
    {
        $this->assertEquals('/opt', resolve(PackageManager::class)->optDir());
        $this->assertEquals('/opt/something.conf', resolve(PackageManager::class)->optDir('something.conf'));
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
        resolve(PackageManager::class)->installOrFail('dnsmasq');
    }
}