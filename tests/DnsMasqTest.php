<?php

use Valet\Brew;
use Valet\DnsMasq;
use Valet\Filesystem;
use Valet\CommandLine;
use Valet\Configuration;
use function Valet\user;
use function Valet\resolve;
use function Valet\swap;
use Illuminate\Container\Container;

class DnsMasqTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
{
    public function set_up()
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container);
    }

    public function tear_down()
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


        $dnsMasq->dnsmasqMasterConfigFile = __DIR__.'/output/dnsmasq.conf';
        $dnsMasq->dnsmasqSystemConfDir = __DIR__.'/output/dnsmasq.d';
        $dnsMasq->resolverPath = __DIR__.'/output/resolver';

        file_put_contents($dnsMasq->dnsmasqMasterConfigFile, file_get_contents(__DIR__.'/files/dnsmasq.conf'));

        $dnsMasq->install('test');

        $this->assertSame('nameserver 127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/resolver/test'));
        $this->assertSame('address=/.test/127.0.0.1'.PHP_EOL.'listen-address=127.0.0.1'.PHP_EOL, file_get_contents(__DIR__.'/output/tld-test.conf'));
        $this->assertSame('test-contents
' . PHP_EOL . 'conf-dir='.BREW_PREFIX.'/etc/dnsmasq.d/,*.conf' . PHP_EOL,
            file_get_contents($dnsMasq->dnsmasqMasterConfigFile)
        );
    }

    public function test_update_tld_removes_old_resolver_and_reinstalls()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietly')->with('rm /etc/resolver/old');
        swap(Configuration::class, $config = Mockery::spy(Configuration::class, ['read' => ['tld' => 'test']]));
        $dnsMasq = Mockery::mock(DnsMasq::class.'[install]', [resolve(Brew::class), $cli, new Filesystem, $config]);
        $dnsMasq->shouldReceive('install')->with('new');
        $dnsMasq->updateTld('old', 'new');
    }
}

class StubForCreatingCustomDnsMasqConfigFiles extends DnsMasq
{
    public function dnsmasqUserConfigDir()
    {
        return __DIR__.'/output/';
    }
}
