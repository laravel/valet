<?php

use Valet\Brew;
use Valet\CommandLine;
use Valet\Configuration as RealConfiguration;
use Valet\Diagnose;
use Valet\DnsMasq;
use Valet\Expose;
use Valet\Filesystem;
use Valet\Nginx;
use Valet\Ngrok;
use Valet\PhpFpm;
use Valet\Site as RealSite;
use function Valet\swap;
use Valet\Valet;

/**
 * @requires PHP >= 8.0
 */
class CliTest extends BaseApplicationTestCase
{
    public function test_tld_command_reads_tld()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'tld']);

        $tester->assertCommandIsSuccessful();

        $this->assertEquals('test', trim($tester->getDisplay()));
    }

    public function test_tld_command_sets_tld()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->setInputs(['Y']);

        $dnsmasq = Mockery::mock(DnsMasq::class);
        $dnsmasq->shouldReceive('updateTld')->with('old', 'buzz')->once();

        $config = Mockery::mock(RealConfiguration::class);
        $config->shouldReceive('read')->andReturn(['tld' => 'old'])->once();
        $config->shouldReceive('updateKey')->with('tld', 'buzz')->once();

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('resecureForNewConfiguration')->with(['tld' => 'old'], ['tld' => 'buzz'])->once();

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('restart')->once();

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('restart')->once();

        swap(DnsMasq::class, $dnsmasq);
        swap(RealConfiguration::class, $config);
        swap(RealSite::class, $site);
        swap(PhpFpm::class, $phpfpm);
        swap(Nginx::class, $nginx);

        $tester->run(['command' => 'tld', 'tld' => 'buzz']);
        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Your Valet TLD has been updated to [buzz]', $tester->getDisplay());
    }

    public function test_loopback_command_reads_loopback()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'loopback']);
        $tester->assertCommandIsSuccessful();

        $this->assertEquals('127.0.0.1', trim($tester->getDisplay()));
    }

    public function test_loopback_command_sets_loopback()
    {
        [$app, $tester] = $this->appAndTester();

        $config = Mockery::mock(RealConfiguration::class);
        $config->shouldReceive('read')->andReturn(['loopback' => '127.9.9.9'])->once();
        $config->shouldReceive('updateKey')->with('loopback', '127.0.0.1')->once();

        $dnsmasq = Mockery::mock(DnsMasq::class);
        $dnsmasq->shouldReceive('refreshConfiguration')->once();

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('aliasLoopback')->with('127.9.9.9', '127.0.0.1')->once();
        $site->shouldReceive('resecureForNewConfiguration')->with(['loopback' => '127.9.9.9'], ['loopback' => '127.0.0.1'])->once();

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('restart')->once();

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('installServer')->once();
        $nginx->shouldReceive('restart')->once();

        swap(RealConfiguration::class, $config);
        swap(DnsMasq::class, $dnsmasq);
        swap(RealSite::class, $site);
        swap(PhpFpm::class, $phpfpm);
        swap(Nginx::class, $nginx);

        $tester->run(['command' => 'loopback', 'loopback' => '127.0.0.1']);
        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Your Valet loopback address has been updated to [127.0.0.1]', $tester->getDisplay());
    }

    public function test_park_command()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'park', 'path' => './tests/output']);

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString(
            "The [./tests/output] directory has been added to Valet's paths.",
            $tester->getDisplay()
        );

        $paths = data_get(Configuration::read(), 'paths');

        $this->assertEquals(1, count($paths));
        $this->assertEquals('./tests/output', reset($paths));
    }

    public function test_status_command_succeeding()
    {
        [$app, $tester] = $this->appAndTester();

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('getLinkedPhpFormula')->andReturn('php@8.2');
        $brew->shouldReceive('hasInstalledPhp')->andReturn(true);
        $brew->shouldReceive('installed')->twice()->andReturn(true);

        $cli = Mockery::mock(CommandLine::class);

        $cli->shouldReceive('run')->once()->andReturn(true);
        $cli->shouldReceive('runAsUser')->once()->with('brew services info --all --json')->andReturn('[{"name":"nginx","running":true}]');
        $cli->shouldReceive('run')->once()->with('brew services info --all --json')->andReturn('[{"name":"nginx","running":true},{"name":"dnsmasq","running":true},{"name":"php@8.2","running":true}]');

        $files = Mockery::mock(Filesystem::class.'[exists]');
        $files->shouldReceive('exists')->once()->andReturn(true);

        swap(Brew::class, $brew);
        swap(CommandLine::class, $cli);
        swap(Filesystem::class, $files);

        $tester->run(['command' => 'status']);

        // $tester->assertCommandIsSuccessful();
        $this->assertStringNotContainsString('No', $tester->getDisplay());
    }

    public function test_status_command_failing()
    {
        [$app, $tester] = $this->appAndTester();

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('getLinkedPhpFormula')->andReturn('php@8.2');
        $brew->shouldReceive('hasInstalledPhp')->andReturn(true);
        $brew->shouldReceive('installed')->twice()->andReturn(true);

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->andReturn(true);
        $cli->shouldReceive('runAsUser')->once()->with('brew services info --all --json')->andReturn('[{"name":"nginx","running":true}]');
        $cli->shouldReceive('run')->once()->with('brew services info --all --json')->andReturn('[{"name":"nginx","running":true}]');

        $files = Mockery::mock(Filesystem::class.'[exists]');
        $files->shouldReceive('exists')->once()->andReturn(false);

        swap(Brew::class, $brew);
        swap(CommandLine::class, $cli);
        swap(Filesystem::class, $files);

        $tester->run(['command' => 'status']);

        $this->assertNotEquals(0, $tester->getStatusCode());
        $this->assertStringContainsString('No', $tester->getDisplay());
    }

    public function test_parked_command()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'parked']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringNotContainsString('test', $tester->getDisplay());

        Configuration::addPath(__DIR__.'/fixtures/Parked/Sites');

        $tester->run(['command' => 'parked']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('my-best-site', $tester->getDisplay());
    }

    public function test_forget_command()
    {
        [$app, $tester] = $this->appAndTester();

        Configuration::addPath(__DIR__.'/fixtures/Parked/Sites');

        $tester->run(['command' => 'forget', 'path' => __DIR__.'/fixtures/Parked/Sites']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringNotContainsString('my-best-site', $tester->getDisplay());
    }

    public function test_link_command()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'link', 'name' => 'tighten']);
        $tester->assertCommandIsSuccessful();

        $this->assertEquals(1, Site::links()->count());
        $this->assertEquals(1, Site::links()->filter(function ($site) {
            return $site['site'] === 'tighten';
        })->count());
    }

    public function test_link_command_defaults_to_cwd()
    {
        [$app, $tester] = $this->appAndTester();

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('link')->with(getcwd(), basename(getcwd()))->once();
        swap(RealSite::class, $site);

        $tester->run(['command' => 'link']);
        $tester->assertCommandIsSuccessful();
    }

    public function test_link_command_with_secure_flag_secures()
    {
        [$app, $tester] = $this->appAndTester();

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('link')->once();
        $site->shouldReceive('domain')->andReturn('mysite.test');
        $site->shouldReceive('secure')->once();
        swap(RealSite::class, $site);

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('restart')->once();
        swap(Nginx::class, $nginx);

        $tester->run(['command' => 'link', '--secure' => true]);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('site has been secured', $tester->getDisplay());
    }

    public function test_links_command()
    {
        [$app, $tester] = $this->appAndTester();

        Site::link(__DIR__.'/fixtures/Parked/Sites/my-best-site', 'tighten');
        $tester->run(['command' => 'links']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('tighten', $tester->getDisplay());
    }

    public function test_unlink_command()
    {
        [$app, $tester] = $this->appAndTester();

        Site::link(getcwd(), basename(getcwd()));

        $tester->run(['command' => 'unlink']);
        $tester->assertCommandIsSuccessful();

        $this->assertEquals(0, Site::links()->count());
    }

    public function test_unlink_command_with_parameter()
    {
        [$app, $tester] = $this->appAndTester();

        Site::link(__DIR__.'/fixtures/Parked/Sites/my-best-site', 'tighten');

        $tester->run(['command' => 'unlink', 'name' => 'tighten']);
        $tester->assertCommandIsSuccessful();

        $this->assertEquals(0, Site::links()->count());
    }

    public function test_unlink_command_unsecures_as_well()
    {
        [$app, $tester] = $this->appAndTester();

        Site::link(__DIR__.'/fixtures/Parked/Sites/my-best-site', 'tighten');

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('domain')->with('tighten')->once()->andReturn('tighten.test');
        $site->shouldReceive('unlink')->with('tighten')->once()->andReturn('tighten');
        $site->shouldReceive('unsecure')->with('tighten.test')->once();
        $site->shouldReceive('isSecured')->with('tighten')->once()->andReturn(true);
        swap(RealSite::class, $site);

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('restart')->once();
        swap(Nginx::class, $nginx);

        $tester->run(['command' => 'unlink', 'name' => 'tighten']);
        $tester->assertCommandIsSuccessful();
    }

    public function test_secure_command()
    {
        [$app, $tester] = $this->appAndTester();

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('domain')->with('tighten')->andReturn('tighten.test');
        $site->shouldReceive('unsecure')->with('tighten.test')->once();
        swap(RealSite::class, $site);

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('restart')->once();
        swap(Nginx::class, $nginx);

        $tester->run(['command' => 'unsecure', 'domain' => 'tighten']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('will now serve traffic over HTTP.', $tester->getDisplay());
    }

    public function test_unsecure_command()
    {
        [$app, $tester] = $this->appAndTester();

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('domain')->andReturn('tighten.test');
        $site->shouldReceive('secure')->with('tighten.test', null, 12345)->once();
        swap(RealSite::class, $site);

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('restart')->once();
        swap(Nginx::class, $nginx);

        $tester->run(['command' => 'secure', 'domain' => 'tighten', '--expireIn' => '12345']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('site has been secured', $tester->getDisplay());
    }

    public function test_unsecure_all_command()
    {
        [$app, $tester] = $this->appAndTester();

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('unSecureAll')->once();
        swap(RealSite::class, $site);

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('restart')->once();
        swap(Nginx::class, $nginx);

        $tester->run(['command' => 'unsecure', '--all' => true]);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('All Valet sites will now serve traffic over HTTP.', $tester->getDisplay());
    }

    public function test_secured_command()
    {
        [$app, $tester] = $this->appAndTester();

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('secured')->andReturn(['tighten.test']);
        swap(RealSite::class, $site);

        $tester->run(['command' => 'secured']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('tighten.test', $tester->getDisplay());
    }

    public function test_proxy_command()
    {
        [$app, $tester] = $this->appAndTester();

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('proxyCreate')->with('elasticsearch', 'http://127.0.0.1:9200', false)->once();

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('restart')->once();

        swap(Nginx::class, $nginx);
        swap(RealSite::class, $site);

        $tester->run(['command' => 'proxy', 'domain' => 'elasticsearch', 'host' => 'http://127.0.0.1:9200']);
        $tester->assertCommandIsSuccessful();
    }

    public function test_unproxy_command()
    {
        [$app, $tester] = $this->appAndTester();

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('proxyDelete')->with('elasticsearch')->once();

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('restart')->once();

        swap(Nginx::class, $nginx);
        swap(RealSite::class, $site);

        $tester->run(['command' => 'unproxy', 'domain' => 'elasticsearch']);
        $tester->assertCommandIsSuccessful();
    }

    public function test_proxies_command()
    {
        [$app, $tester] = $this->appAndTester();

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('proxies')->andReturn(collect([
            ['site' => 'elasticsearch', 'secured' => 'X', 'url' => 'https://elasticsearch.test/', 'host' => 'http://127.0.0.1:9200'],
        ]));

        swap(RealSite::class, $site);

        $tester->run(['command' => 'proxies']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('elasticsearch', $tester->getDisplay());
    }

    public function test_which_command()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'which']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('served by [', $tester->getDisplay());
    }

    public function test_paths_command()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'paths']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('No paths have been registered.', $tester->getDisplay());

        Configuration::addPath(__DIR__);

        $tester->run(['command' => 'paths']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString(__DIR__, $tester->getDisplay());
    }

    public function test_open_command()
    {
        [$app, $tester] = $this->appAndTester();

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->with("open 'http://tighten.test'")->once();
        swap(CommandLine::class, $cli);

        $tester->run(['command' => 'open', 'domain' => 'tighten']);
        $tester->assertCommandIsSuccessful();
    }

    public function test_get_share_tool_command()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'share-tool']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('(not set)', $tester->getDisplay());

        Configuration::updateKey('share-tool', 'expose');

        $tester->run(['command' => 'share-tool']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('expose', $tester->getDisplay());
    }

    public function test_set_share_tool_command()
    {
        [$app, $tester] = $this->appAndTester();

        $expose = Mockery::mock(Expose::class);
        $expose->shouldReceive('installed')->andReturn(true);

        swap(Expose::class, $expose);

        $tester->run(['command' => 'share-tool', 'tool' => 'expose']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('expose', Configuration::read()['share-tool']);

        $ngrok = Mockery::mock(Ngrok::class);
        $ngrok->shouldReceive('installed')->andReturn(true);

        swap(Ngrok::class, $ngrok);

        $tester->run(['command' => 'share-tool', 'tool' => 'ngrok']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('ngrok', Configuration::read()['share-tool']);

        $tester->run(['command' => 'share-tool', 'tool' => 'asdfoijasdofijqoiwejrqwer']);
        $this->assertEquals(1, $tester->getStatusCode()); // Assert comand failure

        $this->assertStringContainsString('ngrok', Configuration::read()['share-tool']);
    }

    public function test_share_tool_prompts_expose_install()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->setInputs(['Y']);

        $expose = Mockery::mock(Expose::class);
        $expose->shouldReceive('installed')->once()->andReturn(false);
        $expose->shouldReceive('ensureInstalled')->once();

        swap(Expose::class, $expose);

        $tester->run(['command' => 'share-tool', 'tool' => 'expose']);
        $tester->assertCommandIsSuccessful();
    }

    public function test_share_tool_prompts_ngrok_install()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->setInputs(['Y']);

        $ngrok = Mockery::mock(Ngrok::class);
        $ngrok->shouldReceive('installed')->once()->andReturn(false);
        $ngrok->shouldReceive('ensureInstalled')->once();

        swap(Ngrok::class, $ngrok);

        $tester->run(['command' => 'share-tool', 'tool' => 'ngrok']);
        $tester->assertCommandIsSuccessful();
    }

    public function test_set_ngrok_token_command()
    {
        [$app, $tester] = $this->appAndTester();

        $ngrok = Mockery::mock(Ngrok::class);
        $ngrok->shouldReceive('setToken')->with('your-token-here')->once()->andReturn('yay');
        swap(Ngrok::class, $ngrok);

        $tester->run(['command' => 'set-ngrok-token', 'token' => 'your-token-here']);
        $tester->assertCommandIsSuccessful();
    }

    public function test_fetch_share_url_command_with_expose()
    {
        [$app, $tester] = $this->appAndTester();

        Configuration::updateKey('share-tool', 'expose');

        $expose = Mockery::mock(Expose::class);
        $expose->shouldReceive('currentTunnelUrl')->once()->andReturn('command-output');
        swap(Expose::class, $expose);

        $tester->run(['command' => 'fetch-share-url']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('command-output', $tester->getDisplay());
    }

    public function test_fetch_share_url_command_with_ngrok()
    {
        [$app, $tester] = $this->appAndTester();

        Configuration::updateKey('share-tool', 'ngrok');

        $ngrok = Mockery::mock(Ngrok::class);
        $ngrok->shouldReceive('currentTunnelUrl')->once()->andReturn('command-output');
        swap(Ngrok::class, $ngrok);

        $tester->run(['command' => 'fetch-share-url']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('command-output', $tester->getDisplay());
    }

    public function test_fetch_share_url_command_with_unset_share_tool()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'fetch-share-url']);
        $this->assertEquals(1, $tester->getStatusCode());

        $this->assertStringContainsString('Please set your share tool', $tester->getDisplay());
    }

    public function test_restart_command()
    {
        [$app, $tester] = $this->appAndTester();

        $dnsmasq = Mockery::mock(DnsMasq::class);
        $dnsmasq->shouldReceive('restart')->once();

        swap(DnsMasq::class, $dnsmasq);

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('restart')->once();

        swap(PhpFpm::class, $phpfpm);

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('restart')->once();

        swap(Nginx::class, $nginx);

        $tester->run(['command' => 'restart']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Valet services have been restarted.', $tester->getDisplay());
    }

    public function test_restart_command_restarts_dnsmasq()
    {
        [$app, $tester] = $this->appAndTester();

        $dnsmasq = Mockery::mock(DnsMasq::class);
        $dnsmasq->shouldReceive('restart')->once();

        swap(DnsMasq::class, $dnsmasq);

        $tester->run(['command' => 'restart', 'service' => 'dnsmasq']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('dnsmasq has been restarted.', $tester->getDisplay());
    }

    public function test_restart_command_restarts_nginx()
    {
        [$app, $tester] = $this->appAndTester();

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('restart')->once();

        swap(Nginx::class, $nginx);

        $tester->run(['command' => 'restart', 'service' => 'nginx']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Nginx has been restarted.', $tester->getDisplay());
    }

    public function test_restart_command_restarts_php()
    {
        [$app, $tester] = $this->appAndTester();

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('restart')->once();

        swap(PhpFpm::class, $phpfpm);

        $tester->run(['command' => 'restart', 'service' => 'php']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('PHP has been restarted.', $tester->getDisplay());
    }

    public function test_start_command()
    {
        [$app, $tester] = $this->appAndTester();

        $dnsmasq = Mockery::mock(DnsMasq::class);
        $dnsmasq->shouldReceive('restart')->once();

        swap(DnsMasq::class, $dnsmasq);

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('restart')->once();

        swap(PhpFpm::class, $phpfpm);

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('restart')->once();

        swap(Nginx::class, $nginx);

        $tester->run(['command' => 'start']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Valet services have been started.', $tester->getDisplay());
    }

    public function test_start_command_starts_dnsmasq()
    {
        [$app, $tester] = $this->appAndTester();

        $dnsmasq = Mockery::mock(DnsMasq::class);
        $dnsmasq->shouldReceive('restart')->once();

        swap(DnsMasq::class, $dnsmasq);

        $tester->run(['command' => 'start', 'service' => 'dnsmasq']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('dnsmasq has been started.', $tester->getDisplay());
    }

    public function test_start_command_starts_nginx()
    {
        [$app, $tester] = $this->appAndTester();

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('restart')->once();

        swap(Nginx::class, $nginx);

        $tester->run(['command' => 'start', 'service' => 'nginx']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Nginx has been started.', $tester->getDisplay());
    }

    public function test_start_command_starts_php()
    {
        [$app, $tester] = $this->appAndTester();

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('restart')->once();

        swap(PhpFpm::class, $phpfpm);

        $tester->run(['command' => 'start', 'service' => 'php']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('PHP has been started.', $tester->getDisplay());
    }

    public function test_stop_command()
    {
        [$app, $tester] = $this->appAndTester();

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('stop');

        swap(Nginx::class, $nginx);

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('stopRunning');

        swap(PhpFpm::class, $phpfpm);

        $tester->run(['command' => 'stop']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Valet services have been stopped.', $tester->getDisplay());
    }

    public function test_stop_command_stops_nginx()
    {
        [$app, $tester] = $this->appAndTester();

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('stop');

        swap(Nginx::class, $nginx);

        $tester->run(['command' => 'stop', 'service' => 'nginx']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Nginx has been stopped', $tester->getDisplay());
    }

    public function test_stop_command_stops_php()
    {
        [$app, $tester] = $this->appAndTester();

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('stopRunning');

        swap(PhpFpm::class, $phpfpm);

        $tester->run(['command' => 'stop', 'service' => 'php']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('PHP has been stopped', $tester->getDisplay());
    }

    public function test_stop_command_handles_bad_services()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'stop', 'service' => 'not-real-services']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Invalid valet service', $tester->getDisplay());
    }

    public function test_force_uninstall_command()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->setInputs(['Y']);

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('unsecureAll')->once();
        $site->shouldReceive('removeCa')->once();
        $site->shouldReceive('uninstallLoopback')->once();

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('uninstall')->once();

        $dnsmasq = Mockery::mock(DnsMasq::class);
        $dnsmasq->shouldReceive('uninstall')->once();

        $config = Mockery::mock(RealConfiguration::class);
        $config->shouldReceive('uninstall')->once();

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('uninstall')->once();

        $valet = Mockery::mock(Valet::class);
        $valet->shouldReceive('unlinkFromUsersBin')->once();
        $valet->shouldReceive('removeSudoersEntry')->once();
        $valet->shouldReceive('forceUninstallText')->once()->andReturn('uninstall-text');

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('removeSudoersEntry')->once();

        swap(RealSite::class, $site);
        swap(Nginx::class, $nginx);
        swap(DnsMasq::class, $dnsmasq);
        swap(RealConfiguration::class, $config);
        swap(PhpFpm::class, $phpfpm);
        swap(Valet::class, $valet);
        swap(Brew::class, $brew);

        $tester->run(['command' => 'uninstall', '--force' => true]);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('YOU ARE ABOUT TO UNINSTALL', $tester->getDisplay());
    }

    public function test_non_force_uninstall_command()
    {
        [$app, $tester] = $this->appAndTester();

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('stopRunning');

        swap(PhpFpm::class, $phpfpm);

        $nginx = Mockery::mock(Nginx::class);
        $nginx->shouldReceive('stop');

        swap(Nginx::class, $nginx);

        $tester->run(['command' => 'uninstall']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('IF YOU WANT TO UNINSTALL VALET MANUALLY', $tester->getDisplay());
    }

    public function test_on_latest_version_command_succeeding()
    {
        [$app, $tester] = $this->appAndTester();

        $valet = Mockery::mock(Valet::class);
        $valet->shouldReceive('onLatestVersion')->once()->andReturn(true);

        swap(Valet::class, $valet);

        $tester->run(['command' => 'on-latest-version']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Yes', $tester->getDisplay());
    }

    public function test_on_latest_version_command_failing()
    {
        [$app, $tester] = $this->appAndTester();

        $valet = Mockery::mock(Valet::class);
        $valet->shouldReceive('onLatestVersion')->once()->andReturn(false);

        swap(Valet::class, $valet);

        $tester->run(['command' => 'on-latest-version']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('not the latest', $tester->getDisplay());
    }

    public function test_trust_command_on()
    {
        [$app, $tester] = $this->appAndTester();

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('createSudoersEntry')->once();

        swap(Brew::class, $brew);

        $valet = Mockery::mock(Valet::class);
        $valet->shouldReceive('createSudoersEntry')->once();

        swap(Valet::class, $valet);

        $tester->run(['command' => 'trust']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('have been added', $tester->getDisplay());
    }

    public function test_trust_command_off()
    {
        [$app, $tester] = $this->appAndTester();

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('removeSudoersEntry')->once();

        swap(Brew::class, $brew);

        $valet = Mockery::mock(Valet::class);
        $valet->shouldReceive('removeSudoersEntry')->once();

        swap(Valet::class, $valet);

        $tester->run(['command' => 'trust', '--off' => true]);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('have been removed', $tester->getDisplay());
    }

    public function test_use_command()
    {
        [$app, $tester] = $this->appAndTester();

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('useVersion')->with('8.2', false);

        swap(PhpFpm::class, $phpfpm);

        $tester->run(['command' => 'use', 'phpVersion' => '8.2']);
        $tester->assertCommandIsSuccessful();
    }

    public function test_use_command_without_specified_version_reads_phprc()
    {
        [$app, $tester] = $this->appAndTester();

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('useVersion')->with('php@8.1', false);

        swap(PhpFpm::class, $phpfpm);

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('linkedPhp')->andReturn('php@8.2');

        swap(Brew::class, $brew);

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('phpRcVersion')->andReturn('php@8.1');

        swap(RealSite::class, $site);

        $tester->run(['command' => 'use']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString(".valetphprc' specifying version: php@8.1", $tester->getDisplay());
    }

    public function test_use_command_without_specified_version_reads_isolation_list()
    {
        [$app, $tester] = $this->appAndTester();

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('useVersion')->with('php@8.0', false);
        $phpfpm->shouldReceive('normalizePhpVersion')->with('php@8.0')->andReturn('php@8.0');

        swap(PhpFpm::class, $phpfpm);

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('linkedPhp')->andReturn('php@8.2');

        swap(Brew::class, $brew);

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('phpRcVersion')->andReturn(null);
        $site->shouldReceive('customPhpVersion')->andReturn('php@8.0');

        swap(RealSite::class, $site);

        $tester->run(['command' => 'use']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('isolated site', $tester->getDisplay());
    }

    public function test_use_command_with_no_specifications()
    {
        [$app, $tester] = $this->appAndTester();

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('linkedPhp')->andReturn('php@8.2');

        swap(Brew::class, $brew);

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('phpRcVersion')->andReturn(null);
        $site->shouldReceive('customPhpVersion')->andReturn(null);

        swap(RealSite::class, $site);

        $tester->run(['command' => 'use']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Valet is using php@8.2', $tester->getDisplay());
    }

    public function test_isolate_command()
    {
        [$app, $tester] = $this->appAndTester();

        // The site this command should assume we're in if we don't pass in --site
        $getcwd = 'valet';

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('isolateDirectory')->with($getcwd, '8.1');

        swap(PhpFpm::class, $phpfpm);

        $tester->run(['command' => 'isolate', 'phpVersion' => '8.1']);
        $tester->assertCommandIsSuccessful();
    }

    public function test_isolate_command_with_phprc()
    {
        [$app, $tester] = $this->appAndTester();

        // The site this command should assume we're in if we don't pass in --site
        $getcwd = 'valet';

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('isolateDirectory')->with($getcwd, '8.2');

        swap(PhpFpm::class, $phpfpm);

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('phpRcVersion')->once()->andReturn('8.2');

        swap(RealSite::class, $site);

        $tester->run(['command' => 'isolate']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('specifying version', $tester->getDisplay());
    }

    public function test_unisolate_command()
    {
        [$app, $tester] = $this->appAndTester();

        // The site this command should assume we're in if we don't pass in --site
        $getcwd = 'valet';

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('unisolateDirectory')->with($getcwd)->once();

        swap(PhpFpm::class, $phpfpm);

        $tester->run(['command' => 'unisolate']);
        $tester->assertCommandIsSuccessful();
    }

    public function test_unisolate_command_with_custom_site()
    {
        [$app, $tester] = $this->appAndTester();

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('unisolateDirectory')->with('my-best-site');

        swap(PhpFpm::class, $phpfpm);

        $tester->run(['command' => 'unisolate', '--site' => 'my-best-site']);
        $tester->assertCommandIsSuccessful();
    }

    public function test_isolated_command()
    {
        [$app, $tester] = $this->appAndTester();

        $phpfpm = Mockery::mock(PhpFpm::class);
        $phpfpm->shouldReceive('isolatedDirectories')->andReturn(collect([['best-directory', '8.1']]));

        swap(PhpFpm::class, $phpfpm);

        $tester->run(['command' => 'isolated']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('best-directory', $tester->getDisplay());
    }

    public function test_which_php_command_reads_nginx()
    {
        [$app, $tester] = $this->appAndTester();

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('host')->once()->andReturn('whatever');
        $site->shouldReceive('customPhpVersion')->once()->andReturn('8.2');

        swap(RealSite::class, $site);

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('getPhpExecutablePath')->with('8.2')->once()->andReturn('testOutput');

        swap(Brew::class, $brew);

        $tester->run(['command' => 'which-php']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('testOutput', $tester->getDisplay());
    }

    public function test_which_php_command_reads_phprc()
    {
        [$app, $tester] = $this->appAndTester();

        $site = Mockery::mock(RealSite::class);
        $site->shouldReceive('host')->once()->andReturn('whatever');
        $site->shouldReceive('customPhpVersion')->once()->andReturn(null);
        $site->shouldReceive('phpRcVersion')->once()->andReturn('8.1');

        swap(RealSite::class, $site);

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('getPhpExecutablePath')->with('8.1')->once()->andReturn('testOutput');

        swap(Brew::class, $brew);

        $tester->run(['command' => 'which-php']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('testOutput', $tester->getDisplay());
    }

    public function test_log_command()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'log']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('In order to tail a log', $tester->getDisplay());
    }

    public function test_log_command_with_key()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'log', 'key' => 'nginx']);
        $tester->assertCommandIsSuccessful();

        // Can't test this because log's output is run via the tail command
        // $this->assertStringContainsString('nginx-error-log-contents', $tester->getDisplay());

        // So instead, we'll test *against* the negative states
        $this->assertStringNotContainsString('does not (yet) exit', $tester->getDisplay());
        $this->assertStringNotContainsString('No logs found', $tester->getDisplay());
        $this->assertStringNotContainsString('In order to tail a log', $tester->getDisplay());
    }

    public function test_directory_listing_command_reads()
    {
        [$app, $tester] = $this->appAndTester();
        Configuration::updateKey('directory-listing', 'off');

        $tester->run(['command' => 'directory-listing']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Directory listing is off', $tester->getDisplay());
    }

    public function test_directory_listing_command_sets()
    {
        [$app, $tester] = $this->appAndTester();
        Configuration::updateKey('directory-listing', 'off');

        $tester->run(['command' => 'directory-listing', 'status' => 'on']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Directory listing setting is now: on', $tester->getDisplay());
    }

    public function test_diagnose_command()
    {
        [$app, $tester] = $this->appAndTester();

        $diagnose = Mockery::mock(Diagnose::class);
        $diagnose->shouldReceive('run')->with(false, false);

        swap(Diagnose::class, $diagnose);

        $tester->run(['command' => 'diagnose']);
        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Diagnostics output', $tester->getDisplay());
    }
}
