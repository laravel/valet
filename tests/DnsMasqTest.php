<?php

use Valet\Contracts\PackageManager;
use Valet\Contracts\ServiceManager;
use Valet\DnsMasq;
use Valet\Filesystem;
use Valet\CommandLine;
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


    public function test_install_installs_and_places_configuration_files_in_proper_locations()
    {
        $pm = Mockery::mock(PackageManager::class);
        $pm->shouldReceive('ensureInstalled')->once()->with('dnsmasq');
        $pm->shouldReceive('optDir')->twice()->andReturnUsing(function ($path) {
            return __DIR__ . ($path ? DIRECTORY_SEPARATOR . $path : $path);
        });
        swap(PackageManager::class, $pm);

        $sm = Mockery::mock(ServiceManager::class);
        $sm->shouldReceive('restart')->once()->with('dnsmasq');
        swap(ServiceManager::class, $sm);

        $dnsMasq = resolve(StubForCreatingCustomDnsMasqConfigFiles::class);

        $dnsMasq->exampleConfigPath = 'files/dnsmasq.conf';
        $dnsMasq->configPath = 'output/dnsmasq.conf';
        $dnsMasq->resolverPath = 'output/resolver';

        $dnsMasq->install('dev');

        $this->assertSame('nameserver 127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/resolver/dev'));
        $this->assertSame('address=/.dev/127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/custom-dnsmasq.conf'));
        $this->assertSame('test-contents

conf-file='.__DIR__.'/output/custom-dnsmasq.conf
', file_get_contents(__DIR__.'/output/dnsmasq.conf'));
    }


    public function test_update_domain_removes_old_resolver_and_reinstalls()
    {
        $pm = Mockery::mock(PackageManager::class);
        $pm->shouldReceive('etcDir')->once();
        swap(PackageManager::class, $pm);
        $sm = Mockery::mock(ServiceManager::class);
        swap(ServiceManager::class, $sm);

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietly')->with('rm /etc/resolver/old');
        $dnsMasq = Mockery::mock(DnsMasq::class.'[install]', [$pm, $sm, $cli, new Filesystem]);
        $dnsMasq->shouldReceive('install')->with('new');
        $dnsMasq->updateDomain('old', 'new');
    }
}


class StubForCreatingCustomDnsMasqConfigFiles extends DnsMasq
{
    public function customConfigPath()
    {
        return __DIR__.'/output/custom-dnsmasq.conf';
    }

    function configFilePath() {
        return __DIR__.'/'.$this->configPath;
    }

    function resolverDirPath() {
        return __DIR__.'/'.$this->resolverPath;
    }
}
