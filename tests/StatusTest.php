<?php

use Illuminate\Container\Container;
use Valet\CommandLine;
use function Valet\resolve;
use Valet\Status;
use function Valet\swap;
use function Valet\user;

class StatusTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
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

    public function test_status_can_be_resolved_from_container()
    {
        $this->assertInstanceOf(Status::class, resolve(Status::class));
    }

    public function test_it_checks_if_brew_services_are_running()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew services info --all --json')->andReturn('[{"name":"nginx","running":true}]');
        $cli->shouldReceive('run')->once()->with('brew services info --all --json')->andReturn('[{"name":"php","running":true}]');

        swap(CommandLine::class, $cli);

        $status = resolve(Status::class);

        $this->assertTrue($status->isBrewServiceRunning('nginx'));
        $this->assertTrue($status->isBrewServiceRunning('php'));
    }

    public function test_it_checks_imprecisely_if_brew_services_are_running()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew services info --all --json')->andReturn('[{"name":"nginx","running":true}]');
        $cli->shouldReceive('run')->once()->with('brew services info --all --json')->andReturn('[{"name":"php@8.1","running":true}]');

        swap(CommandLine::class, $cli);

        $status = resolve(Status::class);

        $this->assertTrue($status->isBrewServiceRunning('nginx'));
        $this->assertTrue($status->isBrewServiceRunning('php', exactMatch: false));
    }
}
