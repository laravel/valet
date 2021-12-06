<?php

use Illuminate\Container\Container;
use Valet\CommandLine;
use Valet\Configuration;
use Valet\Filesystem;
use function Valet\resolve;
use Valet\Site;
use function Valet\swap;
use function Valet\user;

class SiteTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
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

        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->once()
            ->andReturn(['tld' => 'local']);

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config);

        /** @var Site $site */
        $site = resolve(Site::class);

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
        ], $sites->first());
        $this->assertSame([
            'site' => 'sitethree',
            'secured' => ' X',
            'url' => 'https://sitethree.local',
            'path' => $dirPath.'/sitethree',
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

        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->once()
            ->andReturn(['tld' => 'local']);

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config);

        /** @var Site $site */
        $site = resolve(Site::class);

        $sites = $site->getSites($dirPath, collect());
        $this->assertCount(1, $sites);
        $this->assertSame([
            'site' => 'sitetwo',
            'secured' => '',
            'url' => 'http://sitetwo.local',
            'path' => $dirPath.'/sitetwo',
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

        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->once()
            ->andReturn(['tld' => 'local']);

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config);

        /** @var Site $site */
        $site = resolve(Site::class);

        $sites = $site->getSites($dirPath, collect());
        $this->assertCount(1, $sites);
        $this->assertSame([
            'site' => 'siteone',
            'secured' => '',
            'url' => 'http://siteone.local',
            'path' => $dirPath.'/siteone',
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

        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('read')
            ->once()
            ->andReturn(['tld' => 'local']);

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config);

        /** @var Site $site */
        $site = resolve(Site::class);

        $sites = $site->getSites($dirPath, collect());
        $this->assertCount(1, $sites);
        $this->assertSame([
            'site' => 'siteone',
            'secured' => '',
            'url' => 'http://siteone.local',
            'path' => $linkedPath,
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
}

class CommandLineFake extends CommandLine
{
    public function runCommand($command, callable $onError = null)
    {
        // noop
        //
        // This let's us pretend like every command executes correctly
        // so we can (elsewhere) ensure the things we meant to do
        // (like "create a certificate") look like they
        // happened without actually running any
        // commands for real.
    }
}

class FixturesSiteFake extends Site
{
    private $valetHomePath;
    private $crtCounter = 0;

    public function valetHomePath()
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

    public function createCa()
    {
        // noop
        //
        // Most of our certificate testing is primitive and not super
        // "correct" so we're not going to even bother creating the
        // CA for our faked Site.
    }

    public function createCertificate($urlWithTld)
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
        $this->createCertificate($urlWithTld);
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
    public function sitesPath($additionalPath = null)
    {
        return __DIR__.'/output'.($additionalPath ? '/'.$additionalPath : '');
    }
}
