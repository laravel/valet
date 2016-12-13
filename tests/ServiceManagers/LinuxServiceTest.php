<?php

use Illuminate\Container\Container;
use Valet\CommandLine;
use Valet\Contracts\ServiceManager;
use Valet\ServiceManagers\LinuxService;

class LinuxServiceTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container);

        Container::getInstance()->bind(ServiceManager::class, LinuxService::class);
    }


    public function tearDown()
    {
        Mockery::close();
    }


    public function test_start_starts_the_service_using_linux_services()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietly')->once()->with('sudo service dnsmasq start');
        swap(CommandLine::class, $cli);
        resolve(ServiceManager::class)->start('dnsmasq');
    }


    public function test_restart_restarts_the_service_using_linux_services()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietly')->once()->with('sudo service dnsmasq restart');
        swap(CommandLine::class, $cli);
        resolve(ServiceManager::class)->restart('dnsmasq');
    }


    public function test_stop_stops_the_service_using_linux_services()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietly')->once()->with('sudo service dnsmasq stop');
        swap(CommandLine::class, $cli);
        resolve(ServiceManager::class)->stop('dnsmasq');
    }


    public function test_linked_php_returns_real_php_service_name()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('service php-fpm status')
            ->andReturn('Loaded: loaded');
        swap(CommandLine::class, $cli);
        $this->assertEquals('php-fpm', resolve(ServiceManager::class)->getPhpServiceName());

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('service php-fpm status')
            ->andReturn('Loaded: not-found (Reason: No such file or directory)');
        swap(CommandLine::class, $cli);
        $cli->shouldReceive('run')->once()->with('service php' . substr(PHP_VERSION, 0, 3) . '-fpm status')
            ->andReturn('Loaded: loaded');
        swap(CommandLine::class, $cli);
        $this->assertEquals('php' . substr(PHP_VERSION, 0, 3) . '-fpm', resolve(ServiceManager::class)->getPhpServiceName());
    }
}
