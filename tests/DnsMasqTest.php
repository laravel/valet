<?php

use Valet\Brew;
use Valet\DnsMasq;
use Valet\Filesystem;
use Valet\CommandLine;
use Valet\Configuration;
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
        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('ensureInstalled')->once()->with('dnsmasq');
        $brew->shouldReceive('restartService')->once()->with('dnsmasq');
        swap(Brew::class, $brew);

        $dnsMasq = resolve(StubForCreatingCustomDnsMasqConfigFiles::class);

        $dnsMasq->exampleConfigPath = __DIR__.'/files/dnsmasq.conf';
        $dnsMasq->configPath = __DIR__.'/output/dnsmasq.conf';
        $dnsMasq->resolverPath = __DIR__.'/output/resolver';

        $dnsMasq->install('test');

        $this->assertSame('nameserver 127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/resolver/test'));
        $this->assertSame('address=/.test/127.0.0.1'.PHP_EOL.'listen-address=127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/custom-dnsmasq.conf'));
        $this->assertSame('test-contents

conf-file='.__DIR__.'/output/custom-dnsmasq.conf
', file_get_contents(__DIR__.'/output/dnsmasq.conf'));
    }


    public function test_update_domain_removes_old_resolver_and_reinstalls()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietly')->with('rm /etc/resolver/old');
        swap(Configuration::class, Mockery::spy(Configuration::class));
        $dnsMasq = Mockery::mock(DnsMasq::class.'[install]', [resolve(Brew::class), $cli, new Filesystem, resolve(Configuration::class)]);
        $dnsMasq->shouldReceive('install')->with('new');
        $dnsMasq->updateDomain('old', 'new');
    }


    public function test_update_custom_path_domains_creates_config_files()
    {
        swap(Configuration::class, $config = Mockery::spy(Configuration::class, [
            'read' => [
                'paths' => [
                'path-1',
                [
                    'domain' => 'example',
                    'path' => 'path-2'
                ],
                [
                    'domain' => 'custom',
                    'path' => 'path-3'
                ]
            ],
            ]
        ]));
        $dnsMasq = resolve(StubForCreatingCustomDnsMasqConfigFiles::class);

        $dnsMasq->exampleConfigPath = __DIR__.'/files/dnsmasq.conf';
        $dnsMasq->configPath = __DIR__.'/output/dnsmasq.conf';
        $dnsMasq->resolverPath = __DIR__.'/output/resolver';

        $dnsMasq->updateCustomPathDomains();

        $this->assertSame('nameserver 127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/resolver/example'));
        $this->assertSame('nameserver 127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/resolver/custom'));
        $this->assertSame('address=/.example/127.0.0.1'.PHP_EOL.'address=/.custom/127.0.0.1'.PHP_EOL.'listen-address=127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/custom-dnsmasq.conf'));
    }


    public function test_update_custom_path_domains_creates_correct_config_files_after_installation()
    {
        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('ensureInstalled')->once()->with('dnsmasq');
        $brew->shouldReceive('restartService')->once()->with('dnsmasq');
        swap(Brew::class, $brew);
        swap(Configuration::class, $config = Mockery::spy(Configuration::class, [
            'read' => [
                'paths' => [
                'path-1',
                [
                    'domain' => 'example',
                    'path' => 'path-2'
                ],
                [
                    'domain' => 'custom',
                    'path' => 'path-3'
                ]
            ],
            ]
        ]));
        $dnsMasq = resolve(StubForCreatingCustomDnsMasqConfigFiles::class);

        $dnsMasq->exampleConfigPath = __DIR__.'/files/dnsmasq.conf';
        $dnsMasq->configPath = __DIR__.'/output/dnsmasq.conf';
        $dnsMasq->resolverPath = __DIR__.'/output/resolver';

        $dnsMasq->install();

        $dnsMasq->updateCustomPathDomains();

        $this->assertSame('nameserver 127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/resolver/example'));
        $this->assertSame('nameserver 127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/resolver/custom'));
        $this->assertSame('address=/.test/127.0.0.1'.PHP_EOL.'address=/.example/127.0.0.1'.PHP_EOL.'address=/.custom/127.0.0.1'.PHP_EOL.'listen-address=127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/custom-dnsmasq.conf'));
    }
}


class StubForCreatingCustomDnsMasqConfigFiles extends DnsMasq
{
    public function customConfigPath()
    {
        return __DIR__.'/output/custom-dnsmasq.conf';
    }
}
