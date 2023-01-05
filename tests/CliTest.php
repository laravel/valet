<?php

use Valet\Brew;
use Valet\CommandLine;
use Valet\Filesystem;
use Valet\Nginx;
use Valet\Ngrok;
use Valet\Site as RealSite;
use function Valet\swap;

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
        $this->markTestIncomplete();

        // [$app, $tester] = $this->appAndTester();

        // @todo: Mock DnsMasq, Site, PhpFpm, Nginx, Configuration...
        // $tester->setInputs(['Y']);
        // $tester->run(['command' => 'tld', 'tld' => 'buzz']);
        // $tester->assertCommandIsSuccessful();
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
        $this->markTestIncomplete();

        // @todo: Mock everything...
        // [$app, $tester] = $this->appAndTester();

        // $tester->run(['command' => 'loopback', 'loopback' => '127.0.0.9']);
        // $tester->assertCommandIsSuccessful();
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
        $brew->shouldReceive('hasInstalledPhp')->andReturn(true);
        $brew->shouldReceive('installed')->twice()->andReturn(true);

        $cli = Mockery::mock(CommandLine::class);

        $cli->shouldReceive('run')->once()->andReturn(true);
        $cli->shouldReceive('runAsUser')->once()->with('brew services info --all --json')->andReturn('[{"name":"nginx","running":true}]');
        $cli->shouldReceive('run')->once()->with('brew services info --all --json')->andReturn('[{"name":"nginx","running":true},{"name":"dnsmasq","running":true},{"name":"php","running":true}]');

        $files = Mockery::mock(Filesystem::class.'[exists]');
        $files->shouldReceive('exists')->once()->andReturn(true);

        swap(Brew::class, $brew);
        swap(CommandLine::class, $cli);
        swap(Filesystem::class, $files);

        $tester->run(['command' => 'status']);

        // $tester->assertCommandIsSuccessful();
        $this->assertStringNotContainsString('False', $tester->getDisplay());
    }

    public function test_status_command_failing()
    {
        [$app, $tester] = $this->appAndTester();

        $brew = Mockery::mock(Brew::class);
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
        $this->assertStringContainsString('False', $tester->getDisplay());
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

        Site::link(__DIR__.'/fixtures/Parked/Sites/my-best-site', 'tighten');

        $tester->run(['command' => 'unlink', 'name' => 'tighten']);
        $tester->assertCommandIsSuccessful();

        $this->assertEquals(0, Site::links()->count());
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
        $this->markTestIncomplete();
    }

    public function test_unproxy_command()
    {
        $this->markTestIncomplete();
    }

    public function test_proxies_command()
    {
        $this->markTestIncomplete();
    }

    public function test_which_command()
    {
        $this->markTestIncomplete();
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

    public function test_set_ngrok_token_command()
    {
        [$app, $tester] = $this->appAndTester();

        $ngrok = Mockery::mock(Ngrok::class);
        $ngrok->shouldReceive('setToken')->with('your-token-here')->once()->andReturn('yay');
        swap(Ngrok::class, $ngrok);

        $tester->run(['command' => 'set-ngrok-token', 'token' => 'your-token-here']);
        $tester->assertCommandIsSuccessful();
    }

    public function test_start_command()
    {
        $this->markTestIncomplete();
    }

    public function test_restart_command()
    {
        $this->markTestIncomplete();
    }

    public function test_stop_command()
    {
        $this->markTestIncomplete();
    }

    public function test_uninstall_command()
    {
        $this->markTestIncomplete();
    }

    public function test_trust_command()
    {
        $this->markTestIncomplete();
    }

    public function test_use_command()
    {
        $this->markTestIncomplete();
    }

    public function test_isolate_command()
    {
        $this->markTestIncomplete();
    }

    public function test_unisolate_command()
    {
        $this->markTestIncomplete();
    }

    public function test_isolated_command()
    {
        $this->markTestIncomplete();
    }

    public function test_which_php_command()
    {
        $this->markTestIncomplete();
    }

    public function test_composer_command()
    {
        $this->markTestIncomplete();
    }

    public function test_log_command()
    {
        $this->markTestIncomplete();
    }

    public function test_directory_listing_command()
    {
        $this->markTestIncomplete();
    }

    public function test_diagnose_command()
    {
        $this->markTestIncomplete();
    }
}
