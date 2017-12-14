<?php

use Valet\Brew;
use Valet\Site;
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

        $config = Mockery::mock(Configuration::class.'[get]', [Container::getInstance()->make(Filesystem::class)]);
        $config->shouldReceive('get')->with('tld')->andReturn('com');
        $config->shouldReceive('get')->with('subdomain')->andReturn('dev');
        $config->shouldReceive('get')->with('park_tld')->andReturn(null);
        swap(Configuration::class, $config);

        $dnsMasq = resolve(StubForCreatingCustomDnsMasqConfigFiles::class);

        $dnsMasq->exampleConfigPath = __DIR__.'/files/dnsmasq.conf';
        $dnsMasq->configPath = __DIR__.'/output/dnsmasq.conf';
        $dnsMasq->resolverPath = $resolverPath = __DIR__.'/output/resolver';

        $dnsMasq->install([
            'testing-site' => [
                "testing-site",
                "",
                "http://dev.testing-site.com",
                "/Users/me/sites/testing-site",
                $domain = "dev.testing-site.com",
            ]
        ]);

        $this->assertSame('nameserver 127.0.0.1'.PHP_EOL, file_get_contents($resolverPath.'/'.$domain));
        $this->assertSame("address=/.{$domain}/127.0.0.1".PHP_EOL.'listen-address=127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/custom-dnsmasq.conf'));
        $this->assertSame('test-contents

conf-file='.__DIR__.'/output/custom-dnsmasq.conf
', file_get_contents(__DIR__.'/output/dnsmasq.conf'));
    }


    public function test_update_domain_removes_old_resolver_and_reinstalls()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietly')->with('rm /etc/resolver/old');
        $filesystem = Mockery::mock(Filesystem::class);
        $configuration = Mockery::mock(Configuration::class.'[read]', [$filesystem]);
        $site = Mockery::mock(Site::class.'[links]', [$configuration, $cli, $filesystem]);
        $dnsMasq = Mockery::mock(DnsMasq::class.'[install]', [resolve(Brew::class), $cli, $filesystem, $configuration, $site]);
        $site->shouldReceive('links')->andReturn($links = collect([
            'testing-dusk' => [
                "testing-site",
                "",
                "http://www.testing-site.com",
                "/Users/me/sites/testing-site",
                "www.testing-site.com",
            ]
        ]));
        $filesystem->shouldReceive('unlink')->once();
        $filesystem->shouldReceive('putAsUser')->twice();
        $dnsMasq->shouldReceive('install')->with($links->toArray());
        $dnsMasq->shouldReceive('removeDomainResolvers');
        $configuration->shouldReceive('read')->twice()->andReturn([
            'tld'       => 'com',
            'subdomain' => 'dev',
        ]);
        $dnsMasq->updateDomain('com', 'www');
    }
}


class StubForCreatingCustomDnsMasqConfigFiles extends DnsMasq
{
    public function customConfigPath()
    {
        return __DIR__.'/output/custom-dnsmasq.conf';
    }
}
