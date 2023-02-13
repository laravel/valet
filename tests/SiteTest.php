<?php

use Illuminate\Container\Container;
use Valet\Brew;
use Valet\CommandLine;
use Valet\Configuration;
use Valet\Filesystem;
use function Valet\resolve;
use Valet\Site;
use function Valet\swap;
use function Valet\user;

class SiteTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
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
        exec('rm -rf '.__DIR__.'/output');
        mkdir(__DIR__.'/output');
        touch(__DIR__.'/output/.gitkeep');

        Mockery::close();
    }

    public function test_get_certificates_will_return_with_multi_segment_tld()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('scandir')
            ->once()
            ->with($certPath = '/Users/testuser/.config/valet/Certificates')
            ->andReturn(['helloworld.multi.segment.tld.com.crt']);
        $files->shouldReceive('ensureDirExists')
            ->once()
            ->with('/Users/testuser/.config/valet/Certificates', user());
        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->once()
            ->andReturn(['tld' => 'multi.segment.tld.com']);

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config);

        /** @var Site $site */
        $site = resolve(Site::class);
        $certs = $site->getCertificates($certPath);
        $this->assertSame(['helloworld' => 0], $certs->all());
    }

    public function test_get_sites_will_return_if_secured()
    {
        $files = Mockery::mock(Filesystem::class);
        $dirPath = '/Users/usertest/parkedpath';
        $files->shouldReceive('scandir')
            ->once()
            ->with($dirPath)
            ->andReturn(['sitetwo', 'sitethree']);
        $files->shouldReceive('isLink')
            ->andReturn(false);
        $files->shouldReceive('realpath')
            ->twice()
            ->andReturn($dirPath.'/sitetwo', $dirPath.'/sitethree');
        $files->shouldReceive('isDir')->andReturn(true);
        $files->shouldReceive('ensureDirExists')
            ->once()
            ->with($dirPath, user());
        $files->shouldReceive('exists')->andReturn(false);

        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->once()
            ->andReturn(['tld' => 'local']);

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('linkedPhp')->andReturn('php@8.1');

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config);
        swap(Brew::class, $brew);

        /** @var Site $site */
        $site = resolve(Site::class);

        $phpVersion = $site->brew->linkedPhp();

        $certs = Mockery::mock(\Illuminate\Support\Collection::class);
        $certs->shouldReceive('has')
            ->twice()
            ->with(Mockery::on(function ($arg) {
                return $arg === 'sitetwo' || $arg === 'sitethree';
            }))
            ->andReturn(false, true);

        $sites = $site->getSites($dirPath, $certs);

        $this->assertCount(2, $sites);
        $this->assertSame([
            'site' => 'sitetwo',
            'secured' => '',
            'url' => 'http://sitetwo.local',
            'path' => $dirPath.'/sitetwo',
            'phpVersion' => $phpVersion,
        ], $sites->first());
        $this->assertSame([
            'site' => 'sitethree',
            'secured' => ' X',
            'url' => 'https://sitethree.local',
            'path' => $dirPath.'/sitethree',
            'phpVersion' => $phpVersion,
        ], $sites->last());
    }

    public function test_get_sites_will_work_with_non_symlinked_path()
    {
        $files = Mockery::mock(Filesystem::class);
        $dirPath = '/Users/usertest/parkedpath';
        $files->shouldReceive('scandir')
            ->once()
            ->with($dirPath)
            ->andReturn(['sitetwo']);
        $files->shouldReceive('isLink')
            ->once()
            ->with($dirPath.'/sitetwo')
            ->andReturn(false);
        $files->shouldReceive('realpath')
            ->once()
            ->with($dirPath.'/sitetwo')
            ->andReturn($dirPath.'/sitetwo');
        $files->shouldReceive('isDir')->once()->with($dirPath.'/sitetwo')->andReturn(true);
        $files->shouldReceive('ensureDirExists')
            ->once()
            ->with($dirPath, user());
        $files->shouldReceive('exists')->andReturn(false);

        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->once()
            ->andReturn(['tld' => 'local']);

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('linkedPhp')->andReturn('php@8.1');

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config);
        swap(Brew::class, $brew);

        /** @var Site $site */
        $site = resolve(Site::class);

        $phpVersion = $site->brew->linkedPhp();

        $sites = $site->getSites($dirPath, collect());
        $this->assertCount(1, $sites);
        $this->assertSame([
            'site' => 'sitetwo',
            'secured' => '',
            'url' => 'http://sitetwo.local',
            'path' => $dirPath.'/sitetwo',
            'phpVersion' => $phpVersion,
        ], $sites->first());
    }

    public function test_get_sites_will_not_return_if_path_is_not_directory()
    {
        $files = Mockery::mock(Filesystem::class);
        $dirPath = '/Users/usertest/parkedpath';
        $files->shouldReceive('scandir')
            ->once()
            ->with($dirPath)
            ->andReturn(['sitetwo', 'siteone']);
        $files->shouldReceive('isLink')->andReturn(false);
        $files->shouldReceive('realpath')->andReturn($dirPath.'/sitetwo', $dirPath.'/siteone');
        $files->shouldReceive('isDir')->twice()
            ->andReturn(false, true);
        $files->shouldReceive('ensureDirExists')
            ->once()
            ->with($dirPath, user());
        $files->shouldReceive('exists')->andReturn(false);

        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->once()
            ->andReturn(['tld' => 'local']);

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('linkedPhp')->andReturn('php@8.1');

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config);
        swap(Brew::class, $brew);

        /** @var Site $site */
        $site = resolve(Site::class);

        $phpVersion = $site->brew->linkedPhp();

        $sites = $site->getSites($dirPath, collect());
        $this->assertCount(1, $sites);
        $this->assertSame([
            'site' => 'siteone',
            'secured' => '',
            'url' => 'http://siteone.local',
            'path' => $dirPath.'/siteone',
            'phpVersion' => $phpVersion,
        ], $sites->first());
    }

    public function test_get_sites_will_work_with_symlinked_path()
    {
        $files = Mockery::mock(Filesystem::class);
        $dirPath = '/Users/usertest/apath';
        $files->shouldReceive('scandir')
            ->once()
            ->with($dirPath)
            ->andReturn(['siteone']);
        $files->shouldReceive('isLink')
            ->once()
            ->with($dirPath.'/siteone')
            ->andReturn(true);
        $files->shouldReceive('readLink')
            ->once()
            ->with($dirPath.'/siteone')
            ->andReturn($linkedPath = '/Users/usertest/linkedpath/siteone');
        $files->shouldReceive('isDir')->andReturn(true);
        $files->shouldReceive('ensureDirExists')
            ->once()
            ->with($dirPath, user());
        $files->shouldReceive('exists')->andReturn(false);

        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->once()
            ->andReturn(['tld' => 'local']);

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('linkedPhp')->andReturn('php@8.1');

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config);
        swap(Brew::class, $brew);

        /** @var Site $site */
        $site = resolve(Site::class);

        $phpVersion = $site->brew->linkedPhp();

        $sites = $site->getSites($dirPath, collect());
        $this->assertCount(1, $sites);
        $this->assertSame([
            'site' => 'siteone',
            'secured' => '',
            'url' => 'http://siteone.local',
            'path' => $linkedPath,
            'phpVersion' => $phpVersion,
        ], $sites->first());
    }

    public function test_symlink_creates_symlink_to_given_path()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('ensureDirExists')->once()->with(VALET_HOME_PATH.'/Sites', user());
        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('prependPath')->once()->with(VALET_HOME_PATH.'/Sites');
        $files->shouldReceive('symlinkAsUser')->once()->with('target', VALET_HOME_PATH.'/Sites/link');

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config);

        $linkPath = resolve(Site::class)->link('target', 'link');
        $this->assertSame(VALET_HOME_PATH.'/Sites/link', $linkPath);
    }

    public function test_unlink_removes_existing_symlink()
    {
        file_put_contents(__DIR__.'/output/file.out', 'test');
        symlink(__DIR__.'/output/file.out', __DIR__.'/output/link');
        $site = resolve(StubForRemovingLinks::class);
        $site->unlink('link');
        $this->assertFileDoesNotExist(__DIR__.'/output/link');

        $site = resolve(StubForRemovingLinks::class);
        $site->unlink('link');
        $this->assertFileDoesNotExist(__DIR__.'/output/link');
    }

    public function test_prune_links_removes_broken_symlinks_in_sites_path()
    {
        file_put_contents(__DIR__.'/output/file.out', 'test');
        symlink(__DIR__.'/output/file.out', __DIR__.'/output/link');
        unlink(__DIR__.'/output/file.out');
        $site = resolve(StubForRemovingLinks::class);
        $site->pruneLinks();
        $this->assertFileDoesNotExist(__DIR__.'/output/link');
    }

    public function test_certificates_trim_tld_for_custom_tlds()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('ensureDirExists')
            ->once()
            ->with('fake-cert-path', user());
        $files->shouldReceive('scandir')->once()->andReturn([
            'threeletters.dev.crt',
            'fiveletters.local.crt',
        ]);

        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->once()
            ->andReturn(['tld' => 'other']);

        swap(Configuration::class, $config);
        swap(Filesystem::class, $files);

        $site = resolve(Site::class);
        $certs = $site->getCertificates('fake-cert-path')->flip();

        $this->assertEquals('threeletters', $certs->first());
        $this->assertEquals('fiveletters', $certs->last());
    }

    public function test_no_proxies()
    {
        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->andReturn(['tld' => 'test']);

        swap(Configuration::class, $config);

        /** @var FixturesSiteFake $site */
        $site = resolve(FixturesSiteFake::class);

        $site->useOutput();

        $this->assertEquals([], $site->proxies()->all());
    }

    public function test_lists_proxies()
    {
        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->andReturn(['tld' => 'test']);

        swap(Configuration::class, $config);

        /** @var FixturesSiteFake $site */
        $site = resolve(FixturesSiteFake::class);

        $site->useFixture('Proxies');

        $this->assertEquals([
            'some-proxy.com' => [
                'site' => 'some-proxy.com',
                'secured' => ' X',
                'url' => 'https://some-proxy.com.test',
                'path' => 'https://127.0.0.1:8443',
            ],
            'some-other-proxy.com' => [
                'site' => 'some-other-proxy.com',
                'secured' => '',
                'url' => 'http://some-other-proxy.com.test',
                'path' => 'https://127.0.0.1:8443',
            ],
        ], $site->proxies()->all());
    }

    public function test_add_proxy()
    {
        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->andReturn(['tld' => 'test', 'loopback' => VALET_LOOPBACK]);

        swap(Configuration::class, $config);

        swap(CommandLine::class, resolve(CommandLineFake::class));

        /** @var FixturesSiteFake $site */
        $site = resolve(FixturesSiteFake::class);

        $site->useOutput();

        $site->assertCertificateNotExists('my-new-proxy.com.test');
        $site->assertNginxNotExists('my-new-proxy.com.test');

        $site->proxyCreate('my-new-proxy.com', 'https://127.0.0.1:9443', true);

        $site->assertCertificateExistsWithCounterValue('my-new-proxy.com.test', 0);
        $site->assertNginxExists('my-new-proxy.com.test');

        $this->assertEquals([
            'my-new-proxy.com' => [
                'site' => 'my-new-proxy.com',
                'secured' => ' X',
                'url' => 'https://my-new-proxy.com.test',
                'path' => 'https://127.0.0.1:9443',
            ],
        ], $site->proxies()->all());
    }

    public function test_add_non_secure_proxy()
    {
        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->andReturn(['tld' => 'test', 'loopback' => VALET_LOOPBACK]);

        swap(Configuration::class, $config);

        swap(CommandLine::class, resolve(CommandLineFake::class));

        /** @var FixturesSiteFake $site */
        $site = resolve(FixturesSiteFake::class);

        $site->useOutput();

        $site->assertCertificateNotExists('my-new-proxy.com.test');
        $site->assertNginxNotExists('my-new-proxy.com.test');

        $site->proxyCreate('my-new-proxy.com', 'http://127.0.0.1:9443', false);

        $site->assertCertificateNotExists('my-new-proxy.com.test');
        $site->assertNginxExists('my-new-proxy.com.test');

        $this->assertEquals([
            'my-new-proxy.com' => [
                'site' => 'my-new-proxy.com',
                'secured' => '',
                'url' => 'http://my-new-proxy.com.test',
                'path' => 'http://127.0.0.1:9443',
            ],
        ], $site->proxies()->all());
    }

    public function test_add_proxy_clears_previous_proxy_certificate()
    {
        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->andReturn(['tld' => 'test', 'loopback' => VALET_LOOPBACK]);

        swap(Configuration::class, $config);

        swap(CommandLine::class, resolve(CommandLineFake::class));

        /** @var FixturesSiteFake $site */
        $site = resolve(FixturesSiteFake::class);

        $site->useOutput();

        $site->proxyCreate('my-new-proxy.com', 'https://127.0.0.1:7443', true);

        $site->assertCertificateExistsWithCounterValue('my-new-proxy.com.test', 0);

        $this->assertEquals([
            'my-new-proxy.com' => [
                'site' => 'my-new-proxy.com',
                'secured' => ' X',
                'url' => 'https://my-new-proxy.com.test',
                'path' => 'https://127.0.0.1:7443',
            ],
        ], $site->proxies()->all());

        // Note: different proxy port
        $site->proxyCreate('my-new-proxy.com', 'https://127.0.0.1:9443', true);

        // This shows we created a new certificate.
        $site->assertCertificateExistsWithCounterValue('my-new-proxy.com.test', 1);

        $this->assertEquals([
            'my-new-proxy.com' => [
                'site' => 'my-new-proxy.com',
                'secured' => ' X',
                'url' => 'https://my-new-proxy.com.test',
                'path' => 'https://127.0.0.1:9443',
            ],
        ], $site->proxies()->all());
    }

    public function test_add_proxy_clears_previous_non_proxy_certificate()
    {
        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->andReturn(['tld' => 'test', 'loopback' => VALET_LOOPBACK]);

        swap(Configuration::class, $config);

        swap(CommandLine::class, resolve(CommandLineFake::class));

        /** @var FixturesSiteFake $site */
        $site = resolve(FixturesSiteFake::class);

        $site->useOutput();

        $site->fakeSecure('my-new-proxy.com.test');

        // For this to test the correct scenario, we need to ensure the
        // certificate exists but there is not already a proxy Nginx
        // configuration in place.
        $site->assertCertificateExistsWithCounterValue('my-new-proxy.com.test', 0);
        $site->assertNginxNotExists('my-new-proxy.com.test');

        $site->proxyCreate('my-new-proxy.com', 'https://127.0.0.1:9443', true);

        // This shows we created a new certificate.
        $site->assertCertificateExistsWithCounterValue('my-new-proxy.com.test', 1);

        $site->assertNginxExists('my-new-proxy.com.test');

        $this->assertEquals([
            'my-new-proxy.com' => [
                'site' => 'my-new-proxy.com',
                'secured' => ' X',
                'url' => 'https://my-new-proxy.com.test',
                'path' => 'https://127.0.0.1:9443',
            ],
        ], $site->proxies()->all());
    }

    public function test_remove_proxy()
    {
        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->andReturn(['tld' => 'test', 'loopback' => VALET_LOOPBACK]);

        swap(Configuration::class, $config);

        swap(CommandLine::class, resolve(CommandLineFake::class));

        /** @var FixturesSiteFake $site */
        $site = resolve(FixturesSiteFake::class);

        $site->useOutput();

        $site->assertCertificateNotExists('my-new-proxy.com.test');
        $site->assertNginxNotExists('my-new-proxy.com.test');

        $this->assertEquals([], $site->proxies()->all());

        $site->proxyCreate('my-new-proxy.com', 'https://127.0.0.1:9443', true);

        $this->assertEquals([
            'my-new-proxy.com' => [
                'site' => 'my-new-proxy.com',
                'secured' => ' X',
                'url' => 'https://my-new-proxy.com.test',
                'path' => 'https://127.0.0.1:9443',
            ],
        ], $site->proxies()->all());

        $site->assertCertificateExists('my-new-proxy.com.test');
        $site->assertNginxExists('my-new-proxy.com.test');

        $site->proxyDelete('my-new-proxy.com');

        $site->assertCertificateNotExists('my-new-proxy.com.test');
        $site->assertNginxNotExists('my-new-proxy.com.test');

        $this->assertEquals([], $site->proxies()->all());
    }

    public function test_gets_site_url_from_directory()
    {
        $config = Mockery::mock(Configuration::class);

        swap(Configuration::class, $config);

        $siteMock = Mockery::mock(Site::class, [
            resolve(Brew::class),
            resolve(Configuration::class),
            resolve(CommandLine::class),
            resolve(Filesystem::class),
        ])->makePartial();

        swap(Site::class, $siteMock);

        $config->shouldReceive('read')
            ->andReturn(['tld' => 'test', 'loopback' => VALET_LOOPBACK, 'paths' => []]);

        $siteMock->shouldReceive('parked')
            ->andReturn(collect([
                'site1' => [
                    'site' => 'site1',
                    'secured' => '',
                    'url' => 'http://site1.test',
                    'path' => '/Users/name/code/site1',
                ],
            ]));

        $siteMock->shouldReceive('links')->andReturn(collect([
            'site2' => [
                'site' => 'site2',
                'secured' => 'X',
                'url' => 'http://site2.test',
                'path' => '/Users/name/code/site2',
            ],
            'portal.test-site' => [
                'site' => 'portal.test-site',
                'secured' => 'X',
                'url' => 'http://portal.test-site.test',
                'path' => '/Users/name/code/portal.test-site',
            ],
        ]));

        $siteMock->shouldReceive('host')->andReturn('site1');

        $site = resolve(Site::class);

        $this->assertEquals('site1.test', $site->getSiteUrl('.'));
        $this->assertEquals('site1.test', $site->getSiteUrl('./'));

        $this->assertEquals('site1.test', $site->getSiteUrl('site1'));
        $this->assertEquals('site1.test', $site->getSiteUrl('site1.test'));

        $this->assertEquals('site2.test', $site->getSiteUrl('site2'));
        $this->assertEquals('site2.test', $site->getSiteUrl('site2.test'));

        $this->assertEquals('portal.test-site.test', $site->getSiteUrl('portal.test-site'));
        $this->assertEquals('portal.test-site.test', $site->getSiteUrl('portal.test-site.test'));
    }

    public function test_it_throws_getting_nonexistent_site()
    {
        $this->expectException(DomainException::class);
        $config = Mockery::mock(Configuration::class);

        swap(Configuration::class, $config);

        $siteMock = Mockery::mock(Site::class, [
            resolve(Brew::class),
            resolve(Configuration::class),
            resolve(CommandLine::class),
            resolve(Filesystem::class),
        ])->makePartial();

        swap(Site::class, $siteMock);

        $config->shouldReceive('read')
            ->andReturn(['tld' => 'test', 'loopback' => VALET_LOOPBACK, 'paths' => []]);

        $siteMock->shouldReceive('parked')->andReturn(collect());
        $siteMock->shouldReceive('links')->andReturn(collect([]));
        $siteMock->shouldReceive('host')->andReturn('site1');

        $site = resolve(Site::class);
        $this->assertEquals(false, $site->getSiteUrl('site3'));
    }

    public function test_isolation_will_persist_when_adding_ssl_certificate()
    {
        $files = Mockery::mock(Filesystem::class);
        $config = Mockery::mock(Configuration::class);

        $siteMock = Mockery::mock(Site::class, [
            resolve(Brew::class),
            $config,
            Mockery::mock(CommandLine::class),
            $files,
        ])->makePartial();

        swap(Site::class, $siteMock);

        $siteMock->shouldReceive('unsecure');
        $files->shouldReceive('ensureDirExists');
        $files->shouldReceive('putAsUser');
        $siteMock->shouldReceive('createCa');
        $siteMock->shouldReceive('createCertificate');
        $siteMock->shouldReceive('buildSecureNginxServer');

        // If site has an isolated PHP version for the site, it would replace .sock file
        $siteMock->shouldReceive('customPhpVersion')->with('site1.test')->andReturn('73')->once();
        $siteMock->shouldReceive('replaceSockFile')->withArgs([Mockery::any(), '73'])->once();
        resolve(Site::class)->secure('site1.test');

        // For sites without an isolated PHP version, nothing should be replaced
        $siteMock->shouldReceive('customPhpVersion')->with('site2.test')->andReturn(null)->once();
        $siteMock->shouldNotReceive('replaceSockFile');
        resolve(Site::class)->secure('site2.test');
    }

    public function test_isolation_will_persist_when_removing_ssl_certificate()
    {
        $files = Mockery::mock(Filesystem::class);
        $config = Mockery::mock(Configuration::class);
        $cli = Mockery::mock(CommandLine::class);

        $siteMock = Mockery::mock(Site::class, [
            resolve(Brew::class),
            $config,
            $cli,
            $files,
        ])->makePartial();

        swap(Site::class, $siteMock);

        $cli->shouldReceive('run');
        $files->shouldReceive('exists')->andReturn(false);

        // If a site has an isolated PHP version, there should still be a custom nginx site config
        $siteMock->shouldReceive('customPhpVersion')->with('site1.test')->andReturn('73')->once();
        $siteMock->shouldReceive('isolate')->withArgs(['site1.test', '73'])->once();
        resolve(Site::class)->unsecure('site1.test');

        // If a site doesn't have an isolated PHP version, there should no longer be a custom nginx site config
        $siteMock->shouldReceive('customPhpVersion')->with('site2.test')->andReturn(null)->once();
        $siteMock->shouldNotReceive('isolate');
        resolve(Site::class)->unsecure('site2.test');
    }

    public function test_php_version_returns_correct_version_for_site()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('exists')->andReturn(false);

        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('linkedPhp')->andReturn('php@8.1');

        swap(Brew::class, $brew);

        $site = Mockery::mock(Site::class, [
            resolve(Brew::class),
            Mockery::mock(Configuration::class),
            Mockery::mock(CommandLine::class),
            $files,
        ])->makePartial();
        $site->shouldReceive('customPhpVersion')->with('site1.test')->andReturn('73')->once();
        $site->shouldReceive('customPhpVersion')->with('site2.test')->andReturn(null)->once();

        swap(Site::class, $site);

        $phpVersion = $site->brew->linkedPhp();

        $this->assertEquals('php@7.3', $site->getPhpVersion('site1.test'));
        $this->assertEquals($phpVersion, $site->getPhpVersion('site2.test'));
    }

    public function test_can_install_nginx_site_config_for_specific_php_version()
    {
        $files = Mockery::mock(Filesystem::class);
        $config = Mockery::mock(Configuration::class);

        $siteMock = Mockery::mock(Site::class, [
            resolve(Brew::class),
            $config,
            resolve(CommandLine::class),
            $files,
        ])->makePartial();

        $config->shouldReceive('read')
            ->andReturn(['tld' => 'test', 'loopback' => VALET_LOOPBACK]);

        // If Nginx config exists for the site, modify exising config
        $files->shouldReceive('exists')->once()->with($siteMock->nginxPath('site1.test'))->andReturn(true);

        $files->shouldReceive('get')
            ->once()
            ->with($siteMock->nginxPath('site1.test'))
            ->andReturn('# '.ISOLATED_PHP_VERSION.'=php@7.4'.PHP_EOL.'server { fastcgi_pass: valet74.sock }');

        $files->shouldReceive('putAsUser')
            ->once()
            ->withArgs([
                $siteMock->nginxPath('site1.test'),
                '# '.ISOLATED_PHP_VERSION.'=php@8.0'.PHP_EOL.'server { fastcgi_pass: valet80.sock }',
            ]);

        $siteMock->isolate('site1.test', 'php@8.0');

        // When no Nginx file exists, it will create a new config file from the template
        $files->shouldReceive('exists')->once()->with($siteMock->nginxPath('site2.test'))->andReturn(false);
        $files->shouldReceive('getStub')
            ->once()
            ->with('site.valet.conf')
            ->andReturn(file_get_contents(__DIR__.'/../cli/stubs/site.valet.conf'));

        $files->shouldReceive('putAsUser')
            ->once()
            ->withArgs([
                $siteMock->nginxPath('site2.test'),
                Mockery::on(function ($argument) {
                    return preg_match('/^# '.ISOLATED_PHP_VERSION.'=php@8.0/', $argument)
                        && preg_match('#fastcgi_pass "unix:.*/valet80.sock#', $argument)
                        && strpos($argument, 'server_name site2.test www.site2.test *.site2.test;') !== false;
                }),
            ]);

        $siteMock->isolate('site2.test', 'php@8.0');
    }

    public function test_it_removes_isolation()
    {
        $files = Mockery::mock(Filesystem::class);

        $siteMock = Mockery::mock(Site::class, [
            resolve(Brew::class),
            resolve(Configuration::class),
            resolve(CommandLine::class),
            $files,
        ])->makePartial();

        swap(Site::class, $siteMock);

        // SSL Site
        $files->shouldReceive('exists')->once()->with($siteMock->certificatesPath('site1.test', 'crt'))->andReturn(true);
        $files->shouldReceive('putAsUser')->withArgs([$siteMock->nginxPath('site1.test'), Mockery::any()])->once();
        $siteMock->shouldReceive('buildSecureNginxServer')->once()->with('site1.test');
        resolve(Site::class)->removeIsolation('site1.test');

        // Non-SSL Site
        $files->shouldReceive('exists')->once()->with($siteMock->certificatesPath('site2.test', 'crt'))->andReturn(false);
        $files->shouldReceive('unlink')->with($siteMock->nginxPath('site2.test'))->once();
        $siteMock->shouldNotReceive('buildSecureNginxServer')->with('site2.test');
        resolve(Site::class)->removeIsolation('site2.test');
    }

    public function test_retrieves_custom_php_version_from_nginx_config()
    {
        $files = Mockery::mock(Filesystem::class);

        $siteMock = Mockery::mock(Site::class, [
            resolve(Brew::class),
            resolve(Configuration::class),
            resolve(CommandLine::class),
            $files,
        ])->makePartial();

        swap(Site::class, $siteMock);

        // Site with isolated PHP version
        $files->shouldReceive('exists')->once()->with($siteMock->nginxPath('site1.test'))->andReturn(true);
        $files->shouldReceive('get')
            ->once()
            ->with($siteMock->nginxPath('site1.test'))
            ->andReturn('# '.ISOLATED_PHP_VERSION.'=php@7.4');
        $this->assertEquals('74', resolve(Site::class)->customPhpVersion('site1.test'));

        // Site without any custom nginx config
        $files->shouldReceive('exists')->once()->with($siteMock->nginxPath('site2.test'))->andReturn(false);
        $files->shouldNotReceive('get')->with($siteMock->nginxPath('site2.test'));
        $this->assertEquals(null, resolve(Site::class)->customPhpVersion('site2.test'));

        // Site with a custom nginx config, but doesn't have the header
        $files->shouldReceive('exists')->once()->with($siteMock->nginxPath('site3.test'))->andReturn(true);
        $files->shouldReceive('get')
            ->once()
            ->with($siteMock->nginxPath('site3.test'))
            ->andReturn('server { }');
        $this->assertEquals(null, resolve(Site::class)->customPhpVersion('site3.test'));
    }

    public function test_replace_sock_file_in_nginx_config()
    {
        $site = resolve(Site::class);

        // When switching to php71, valet71.sock should be replaced with valet.sock;
        // isolation header should be prepended
        $this->assertEquals(
            '# '.ISOLATED_PHP_VERSION.'=71'.PHP_EOL.'server { fastcgi_pass: valet71.sock }',
            $site->replaceSockFile('server { fastcgi_pass: valet71.sock }', '71')
        );

        // When switching to php72, valet.sock should be replaced with valet72.sock
        $this->assertEquals(
            '# '.ISOLATED_PHP_VERSION.'=72'.PHP_EOL.'server { fastcgi_pass: valet72.sock }',
            $site->replaceSockFile('server { fastcgi_pass: valet.sock }', '72')
        );

        // When switching to php73 from php72, valet72.sock should be replaced with valet73.sock;
        // isolation header should be updated to php@7.3
        $this->assertEquals(
            '# '.ISOLATED_PHP_VERSION.'=73'.PHP_EOL.'server { fastcgi_pass: valet73.sock }',
            $site->replaceSockFile('# '.ISOLATED_PHP_VERSION.'=72'.PHP_EOL.'server { fastcgi_pass: valet72.sock }', '73')
        );

        // When switching to php72 from php74, valet72.sock should be replaced with valet74.sock;
        // isolation header should be updated to php@7.4
        $this->assertEquals(
            '# '.ISOLATED_PHP_VERSION.'=php@7.4'.PHP_EOL.'server { fastcgi_pass: valet74.sock }',
            $site->replaceSockFile('# '.ISOLATED_PHP_VERSION.'=72'.PHP_EOL.'server { fastcgi_pass: valet.sock }', 'php@7.4')
        );
    }

    public function test_it_returns_secured_sites()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('scandir')
            ->once()
            ->andReturn(['helloworld.tld.crt', '.DS_Store']);

        swap(Filesystem::class, $files);

        $site = resolve(Site::class);
        $sites = $site->secured();

        $this->assertSame(['helloworld.tld'], $sites);
    }

    public function test_it_returns_true_if_a_site_is_secured()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('scandir')
            ->once()
            ->andReturn(['helloworld.tld.crt', '.DS_Store']);

        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->once()
            ->andReturn(['tld' => 'tld']);

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config);

        $site = resolve(Site::class);

        $this->assertTrue($site->isSecured('helloworld'));
    }

    public function test_it_can_read_valet_rc_files()
    {
        resolve(Configuration::class)->addPath(__DIR__.'/fixtures/Parked/Sites');
        $site = resolve(Site::class);

        $this->assertEquals([
            'item' => 'value',
            'php' => 'php@8.0',
            'other_item' => 'othervalue',
        ], $site->valetRc('site-w-valetrc-1'));

        $this->assertEquals([
            'php' => 'php@8.1',
        ], $site->valetRc('site-w-valetrc-2'));

        $this->assertEquals([
            'item' => 'value',
            'php' => 'php@8.2',
        ], $site->valetRc('site-w-valetrc-3'));
    }

    public function test_it_can_read_php_rc_version()
    {
        resolve(Configuration::class)->addPath(__DIR__.'/fixtures/Parked/Sites');
        $site = resolve(Site::class);

        $this->assertEquals('php@8.1', $site->phpRcVersion('site-w-valetphprc-1'));
        $this->assertEquals('php@8.0', $site->phpRcVersion('site-w-valetphprc-2'));
        $this->assertEquals(null, $site->phpRcVersion('my-best-site'));
        $this->assertEquals(null, $site->phpRcVersion('non-existent-site'));
        $this->assertEquals('php@8.0', $site->phpRcVersion('site-w-valetrc-1'));
        $this->assertEquals('php@8.1', $site->phpRcVersion('site-w-valetrc-2'));
        $this->assertEquals('php@8.2', $site->phpRcVersion('site-w-valetrc-3'));
        $this->assertEquals('php@8.2', $site->phpRcVersion('blabla', __DIR__.'/fixtures/Parked/Sites/site-w-valetrc-3'));
    }
}

class CommandLineFake extends CommandLine
{
    public function runCommand(string $command, callable $onError = null): string
    {
        // noop
        //
        // This lets us pretend like every command executes correctly
        // so we can (elsewhere) ensure the things we meant to do
        // (like "create a certificate") look like they
        // happened without actually running any
        // commands for real.

        return 'hooray!';
    }
}

class FixturesSiteFake extends Site
{
    private $valetHomePath;

    private $crtCounter = 0;

    public function valetHomePath(): string
    {
        if (! isset($this->valetHomePath)) {
            throw new \RuntimeException(static::class.' needs to be configured using useFixtures or useOutput');
        }

        return $this->valetHomePath;
    }

    /**
     * Use a named fixture (tests/fixtures/[Name]) for this
     * instance of the Site.
     */
    public function useFixture($fixtureName)
    {
        $this->valetHomePath = __DIR__.'/fixtures/'.$fixtureName;
    }

    /**
     * Use the output directory (tests/output) for this instance
     * of the Site.
     */
    public function useOutput()
    {
        $this->valetHomePath = __DIR__.'/output';
    }

    public function createCa(int $caExpireInDays): void
    {
        // noop
        //
        // Most of our certificate testing is primitive and not super
        // "correct" so we're not going to even bother creating the
        // CA for our faked Site.
    }

    public function createCertificate(string $urlWithTld, int $caExpireInDays): void
    {
        // We're not actually going to generate a real certificate
        // here. We are going to do something basic to include
        // the URL and a counter so we can see if this
        // method was called when we expect and also
        // ensure a file is written out in the
        // expected and correct place.

        $crtPath = $this->certificatesPath($urlWithTld, 'crt');
        $keyPath = $this->certificatesPath($urlWithTld, 'key');

        $counter = $this->crtCounter++;

        file_put_contents($crtPath, 'crt:'.$urlWithTld.':'.$counter);
        file_put_contents($keyPath, 'key:'.$urlWithTld.':'.$counter);
    }

    public function fakeSecure($urlWithTld)
    {
        // This method is just used to ensure we all understand that we are
        // forcing a fake creation of a URL (including .tld) and passes
        // through to createCertificate() directly.
        $this->files->ensureDirExists($this->certificatesPath(), user());
        $this->createCertificate($urlWithTld, 368);
    }

    public function assertNginxExists($urlWithTld)
    {
        SiteTest::assertFileExists($this->nginxPath($urlWithTld));
    }

    public function assertNginxNotExists($urlWithTld)
    {
        SiteTest::assertFileDoesNotExist($this->nginxPath($urlWithTld));
    }

    public function assertCertificateExists($urlWithTld)
    {
        SiteTest::assertFileExists($this->certificatesPath($urlWithTld, 'crt'));
        SiteTest::assertFileExists($this->certificatesPath($urlWithTld, 'key'));
    }

    public function assertCertificateNotExists($urlWithTld)
    {
        SiteTest::assertFileDoesNotExist($this->certificatesPath($urlWithTld, 'crt'));
        SiteTest::assertFileDoesNotExist($this->certificatesPath($urlWithTld, 'key'));
    }

    public function assertCertificateExistsWithCounterValue($urlWithTld, $counter)
    {
        // Simple test to assert the certificate for the specified
        // URL (including .tld) exists and has the expected
        // fake contents.

        $this->assertCertificateExists($urlWithTld);

        $crtPath = $this->certificatesPath($urlWithTld, 'crt');
        $keyPath = $this->certificatesPath($urlWithTld, 'key');

        SiteTest::assertEquals('crt:'.$urlWithTld.':'.$counter, file_get_contents($crtPath));
        SiteTest::assertEquals('key:'.$urlWithTld.':'.$counter, file_get_contents($keyPath));
    }
}

class StubForRemovingLinks extends Site
{
    public function sitesPath(?string $additionalPath = null): string
    {
        return __DIR__.'/output'.($additionalPath ? '/'.$additionalPath : '');
    }
}
