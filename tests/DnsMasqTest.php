<?php

use Valet\Brew;
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
        exec('rm -rf '.__DIR__.'/output/dev');
        mkdir(__DIR__.'/output/dev', 0777);
        touch(__DIR__.'/output/.gitkeep');

        Mockery::close();
    }


    public function test_install_installs_and_places_configuration_files_in_proper_locations()
    {
        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('ensureInstalled')->once()->with('dnsmasq');
        $brew->shouldReceive('restartService')->once()->with('dnsmasq');
        swap(Brew::class, $brew);

        $dnsMasq = resolve(StubForCreatingCustomDnsMasqConfigFiles::class);

        $dnsMasq->install('dev');

        $this->assertSame('nameserver 127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/resolver/dev'));
        $this->assertSame('address=/.dev/127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/custom-dnsmasq.conf'));
        $this->assertSame('test-contents

conf-file='.__DIR__.'/output/custom-dnsmasq.conf
', file_get_contents(__DIR__.'/output/dnsmasq.conf'));
    }

    public function test_rename_domain_resolver_and_rename_in_configuration_file()
    {
        $dnsMasq = Mockery::mock(StubForCreatingCustomDnsMasqConfigFiles::class.'[restart]', [resolve(Brew::class), resolve(CommandLine::class), new Filesystem]);
        $dnsMasq->shouldReceive('restart');

        $dnsMasq->install('dev');

        $dnsMasq->renameDomain('dev', 'valet');

        $this->assertSame('address=/.valet/127.0.0.1'.PHP_EOL, file_get_contents($dnsMasq->customConfigPath()));
    }

    public function test_delete_domain_resolver_and_remove_from_configuration_file()
    {
        $dnsMasq = Mockery::mock(StubForCreatingCustomDnsMasqConfigFiles::class.'[restart]', [resolve(Brew::class), resolve(CommandLine::class), new Filesystem]);
        $dnsMasq->shouldReceive('restart');

        $dnsMasq->install('dev');

        $dnsMasq->deleteDomain('dev');

        $this->assertSame('', file_get_contents($dnsMasq->customConfigPath()));
    }
}


class StubForCreatingCustomDnsMasqConfigFiles extends DnsMasq
{
    public $exampleConfigPath = __DIR__.'/files/dnsmasq.conf';
    public $configPath = __DIR__.'/output/dnsmasq.conf';
    public $resolverPath = __DIR__.'/output/resolver';

    public function customConfigPath()
    {
        return __DIR__.'/output/custom-dnsmasq.conf';
    }
}
