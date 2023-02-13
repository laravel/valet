<?php

use Valet\CommandLine;
use Valet\Os\Installer;
use Valet\Os\Mac\Brew;
use Valet\Os\Mac\MacStatus;
use function Valet\resolve;
use Valet\Status;
use function Valet\swap;
use function Valet\user;

class MacStatusTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
{
    use UsesNullWriter;

    public function set_up()
    {
        $_SERVER['SUDO_USER'] = user();

        $this->setNullWriter();
        swap(Installer::class, resolve(Brew::class));
    }

    public function tear_down()
    {
        Mockery::close();
    }

    public function test_status_can_be_resolved_from_container()
    {
        $this->assertInstanceOf(MacStatus::class, resolve(MacStatus::class));

        swap(Status::class, resolve(MacStatus::class));
        $this->assertInstanceOf(MacStatus::class, resolve(Status::class));
    }

    public function test_it_checks_if_brew_services_are_running()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew services info --all --json')->andReturn('[{"name":"nginx","running":true}]');
        $cli->shouldReceive('run')->once()->with('brew services info --all --json')->andReturn('[{"name":"php","running":true}]');

        swap(CommandLine::class, $cli);

        $status = resolve(MacStatus::class);

        $this->assertTrue($status->isBrewServiceRunning('nginx'));
        $this->assertTrue($status->isBrewServiceRunning('php'));
    }

    public function test_it_checks_imprecisely_if_brew_services_are_running()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew services info --all --json')->andReturn('[{"name":"nginx","running":true}]');
        $cli->shouldReceive('run')->once()->with('brew services info --all --json')->andReturn('[{"name":"php@8.1","running":true}]');

        swap(CommandLine::class, $cli);

        $status = resolve(MacStatus::class);

        $this->assertTrue($status->isBrewServiceRunning('nginx'));
        $this->assertTrue($status->isBrewServiceRunning('php', exactMatch: false));
    }
}
