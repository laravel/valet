<?php

use Valet\DnsMasq;
use Valet\Filesystem;
use Valet\CommandLine;
use Valet\Contracts\PackageManager;
use Valet\Contracts\ServiceManager;
use Illuminate\Container\Container;

class DnsMasqTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['SUDO_USER'] = user();
        Container::setInstance(new Container);
    }


    public function tearDown()
    {
        exec('rm -rf '.__DIR__.'/output');
        mkdir(__DIR__.'/output');
        touch(__DIR__.'/output/.gitkeep');

        Mockery::close();
    }


    public function test_install_calls_the_right_methods_and_restarts()
    {
        $files = Mockery::mock(CommandLine::class);
        $cli = Mockery::mock(Filesystem::class);
        $sm = Mockery::mock(ServiceManager::class);
        $pm = Mockery::mock(PackageManager::class);

        $dnsMasq = Mockery::mock(DnsMasq::class.'[dnsmasqSetup,createCustomConfigFile]', [$pm, $sm, $cli, $files]);

        $dnsMasq->shouldReceive('dnsmasqSetup')->once();
        $dnsMasq->shouldReceive('createCustomConfigFile')->once()->with('dev');
        $pm->shouldReceive('dnsmasqRestart')->once()->with($sm);
        $dnsMasq->install();

        $dnsMasq->shouldReceive('dnsmasqSetup')->once();
        $dnsMasq->shouldReceive('createCustomConfigFile')->once()->with('test');
        $pm->shouldReceive('dnsmasqRestart')->once()->with($sm);
        $dnsMasq->install('test');
    }

    public function test_dnsmasqSetup_correctly_installs_and_configures_dnsmasq_control_to_networkmanager()
    {
        copy(__DIR__.'/files/NetworkManager.conf', __DIR__.'/output/NetworkManager.conf');

        $pm = Mockery::mock(PackageManager::class);
        $pm->shouldReceive('ensureInstalled')->once()->with('dnsmasq');
        $sm = Mockery::mock(ServiceManager::class);

        swap(PackageManager::class, $pm);
        swap(ServiceManager::class, $sm);

        $dnsMasq = resolve(DnsMasq::class);
        $dnsMasq->nmConfigPath = __DIR__.'/output/NetworkManager.conf';

        $dnsMasq->dnsmasqSetup();

        $this->assertSame('[main]
dns=dnsmasq
test-contents
', file_get_contents(__DIR__.'/output/NetworkManager.conf'));
    }

    public function test_createCustomConfigFile_correctly_creates_valet_dns_config_file()
    {
        $pm = Mockery::mock(PackageManager::class);
        $sm = Mockery::mock(ServiceManager::class);

        swap(PackageManager::class, $pm);
        swap(ServiceManager::class, $sm);

        $dnsMasq = resolve(DnsMasq::class);
        $dnsMasq->configPath = __DIR__.'/output/valet';

        $dnsMasq->createCustomConfigFile('test');

        $this->assertSame('address=/.test/127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/valet'));
    }

    public function test_update_domain_removes_old_resolver_and_reinstalls()
    {
        $pm = Mockery::mock(PackageManager::class);
        $sm = Mockery::mock(ServiceManager::class);
        $cli = Mockery::mock(Filesystem::class);
        $files = Mockery::mock(CommandLine::class);

        $dnsMasq = Mockery::mock(DnsMasq::class.'[install]', [$pm, $sm, $cli, $files]);
        $dnsMasq->shouldReceive('install')->with('new');
        $dnsMasq->updateDomain('old', 'new');
    }
}
