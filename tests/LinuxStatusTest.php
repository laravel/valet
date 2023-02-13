<?php

use Valet\CommandLine;
use Valet\Os\Installer;
use Valet\Os\Linux\LinuxStatus;
use Valet\Os\Mac\Brew;
use function Valet\resolve;
use Valet\Status;
use function Valet\swap;
use function Valet\user;

class LinuxStatusTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
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
        $this->assertInstanceOf(LinuxStatus::class, resolve(LinuxStatus::class));

        swap(Status::class, resolve(LinuxStatus::class));
        $this->assertInstanceOf(LinuxStatus::class, resolve(Status::class));
    }

    public function test_it_checks_if_apt_services_are_running()
    {
        $this->markTestIncomplete();

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew services info --all --json')->andReturn('[{"name":"nginx","running":true}]');
        $cli->shouldReceive('run')->once()->with('brew services info --all --json')->andReturn('[{"name":"php","running":true}]');

        swap(CommandLine::class, $cli);

        $status = resolve(LinuxStatus::class);

        $this->assertTrue($status->isBrewServiceRunning('nginx'));
        $this->assertTrue($status->isBrewServiceRunning('php'));
    }

    public function test_it_gets_service_active_line_value()
    {
        $status = resolve(LinuxStatus::class);

        $output = '•dnsmasq.service - dnsmasq - A lightweight HDCP and caching DNS server
            Loaded: loaded (/lib/systemd/system/dnsmasq.service; enabled; vendor preset: enabled)
            Active: failed (Result: exit-code) since Mon 1999-01-01 01:01:01 EST; 237 min ago
           Process: 12345 ExecStartPre=/usr/sbin/dnsmasq --test (code=exited, status=3)

Jan 11 11:11:11 user-whatever systemd[1]: Starting dnsmasq
Jan 11 11:11:11 user-whatever systemd[1]: FAILED to start up';

        $this->assertEquals('failed', $status->getServiceActiveLineValue($output));

        $output = 'This string does not contain the key string at all';

        $this->assertNull($status->getServiceActiveLineValue($output));
    }
}
