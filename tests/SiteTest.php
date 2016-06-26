<?php

use Valet\Site;
use Valet\Filesystem;
use Valet\Configuration;
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
        exec('rm -rf '.__DIR__.'/output/dev');
        exec('rm -rf '.__DIR__.'/output/file.out');
        mkdir(__DIR__.'/output/dev/', 0777);
        touch(__DIR__.'/output/.gitkeep');

        Mockery::close();
    }


    public function test_symlink_creates_symlink_to_given_path()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('ensureDirExists')->once()->with(VALET_HOME_PATH.'/Sites/dev', user());
        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('prependPath')->once()->with('dev', VALET_HOME_PATH.'/Sites/dev');
        $files->shouldReceive('symlinkAsUser')->once()->with('target', VALET_HOME_PATH.'/Sites/dev/link');

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config);

        $linkPath = resolve(Site::class)->link('dev', 'target', 'link');
        $this->assertSame(VALET_HOME_PATH.'/Sites/dev/link', $linkPath);
    }

    public function test_unlink_removes_existing_symlink()
    {
        $config = Mockery::mock(Configuration::class, [new Filesystem]);
        $config->shouldReceive('removePath');
        swap(Configuration::class, $config);

        file_put_contents(__DIR__.'/output/file.out', 'test');
        symlink(__DIR__.'/output/file.out', __DIR__.'/output/dev/link');
        $site = resolve(StubForRemovingLinks::class);
        $site->unlink('dev', 'link');
        $this->assertFileNotExists(__DIR__.'/output/dev/link');

        $site = resolve(StubForRemovingLinks::class);
        $site->unlink('dev', 'link');
        $this->assertFileNotExists(__DIR__.'/output/dev/link');
    }

    public function test_prune_links_removes_broken_symlinks_in_sites_path()
    {
        file_put_contents(__DIR__.'/output/file.out', 'test');
        symlink(__DIR__.'/output/file.out', __DIR__.'/output/dev/link');
        unlink(__DIR__.'/output/file.out');
        $site = resolve(StubForRemovingLinks::class);
        $site->pruneLinks();
        $this->assertFileNotExists(__DIR__.'/output/dev/link');
    }

    public function test_logs_method_returns_array_of_log_files()
    {
        $logs = resolve(Site::class)->logs([[
            'domain' => 'dev',
            'paths' => [__DIR__.'/test-directory-for-logs/dev']
        ]]);
        $this->assertSame(__DIR__ . '/test-directory-for-logs/dev/project/storage/logs/laravel.log', $logs[0]);
        unlink(__DIR__ . '/test-directory-for-logs/dev/project/storage/logs/laravel.log');
    }
}


class StubForRemovingLinks extends Site
{
    function sitesPath()
    {
        return __DIR__.'/output';
    }
}
