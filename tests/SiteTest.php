<?php

use Valet\Site;
use Valet\Filesystem;
use Valet\Configuration;
use function Valet\user;
use function Valet\resolve;
use function Valet\swap;
use Illuminate\Container\Container;

class SiteTest extends PHPUnit_Framework_TestCase
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


    public function test_get_certificates_will_return_with_multi_segment_tld()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('scandir')
            ->once()
            ->with($certPath = '/Users/testuser/.config/valet/Certificates')
            ->andReturn(['helloworld.multi.segment.tld.com.crt']);
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
            ->andReturn($dirPath . '/sitetwo', $dirPath . '/sitethree');
        $files->shouldReceive('isDir')->andReturn(true);

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
            'path' => $dirPath . '/sitetwo',
        ], $sites->first());
        $this->assertSame([
            'site' => 'sitethree',
            'secured' => ' X',
            'url' => 'https://sitethree.local',
            'path' => $dirPath . '/sitethree',
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
            ->with($dirPath . '/sitetwo')
            ->andReturn(false);
        $files->shouldReceive('realpath')
            ->once()
            ->with($dirPath . '/sitetwo')
            ->andReturn($dirPath . '/sitetwo');
        $files->shouldReceive('isDir')->once()->with($dirPath . '/sitetwo')->andReturn(true);

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
            'path' => $dirPath . '/sitetwo',
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
        $files->shouldReceive('realpath')->andReturn($dirPath . '/sitetwo', $dirPath . '/siteone');
        $files->shouldReceive('isDir')->twice()
            ->andReturn(false, true);

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
            'path' => $dirPath . '/siteone',
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
            ->with($dirPath . '/siteone')
            ->andReturn(true);
        $files->shouldReceive('readLink')
            ->once()
            ->with($dirPath . '/siteone')
            ->andReturn($linkedPath = '/Users/usertest/linkedpath/siteone');
        $files->shouldReceive('isDir')->andReturn(true);

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
        $this->assertFileNotExists(__DIR__.'/output/link');

        $site = resolve(StubForRemovingLinks::class);
        $site->unlink('link');
        $this->assertFileNotExists(__DIR__.'/output/link');
    }


    public function test_prune_links_removes_broken_symlinks_in_sites_path()
    {
        file_put_contents(__DIR__.'/output/file.out', 'test');
        symlink(__DIR__.'/output/file.out', __DIR__.'/output/link');
        unlink(__DIR__.'/output/file.out');
        $site = resolve(StubForRemovingLinks::class);
        $site->pruneLinks();
        $this->assertFileNotExists(__DIR__.'/output/link');
    }


    public function test_certificates_trim_tld_for_custom_tlds()
    {
        $files = Mockery::mock(Filesystem::class);
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
}


class StubForRemovingLinks extends Site
{
    function sitesPath()
    {
        return __DIR__.'/output';
    }
}
