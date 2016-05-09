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
        exec('rm -rf '.__DIR__.'/output');
        mkdir(__DIR__.'/output');
        touch(__DIR__.'/output/.gitkeep');

        Mockery::close();
    }


    public function test_symlink_creates_symlink_to_given_path()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('ensureDirExists')->once()->with(VALET_HOME_PATH.'/Sites', user());
        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('prependPath')->once()->with(VALET_HOME_PATH.'/Sites');
        $files->shouldReceive('symlink')->once()->with('target', VALET_HOME_PATH.'/Sites/link');

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config);

        $linkPath = resolve(Site::class)->link('target', 'link');
        $this->assertEquals(VALET_HOME_PATH.'/Sites/link', $linkPath);
    }


    public function test_unlink_removes_existing_symlink()
    {
        file_put_contents(__DIR__.'/output/file.out', 'test');
        symlink(__DIR__.'/output/file.out', __DIR__.'/output/link');
        $site = resolve(StubForRemovingLinks::class);
        $site->unlink('link');
        $this->assertFalse(file_exists(__DIR__.'/output/link'));

        $site = resolve(StubForRemovingLinks::class);
        $site->unlink('link');
        $this->assertFalse(file_exists(__DIR__.'/output/link'));
    }


    public function test_prune_links_removes_broken_symlinks_in_sites_path()
    {
        file_put_contents(__DIR__.'/output/file.out', 'test');
        symlink(__DIR__.'/output/file.out', __DIR__.'/output/link');
        unlink(__DIR__.'/output/file.out');
        $site = resolve(StubForRemovingLinks::class);
        $site->pruneLinks();
        $this->assertFalse(file_exists(__DIR__.'/output/link'));
    }


    public function test_logs_method_returns_array_of_log_files()
    {
        $logs = resolve(Site::class)->logs([__DIR__.'/test-directory-for-logs']);
        $this->assertEquals(__DIR__.'/test-directory-for-logs/project/storage/logs/laravel.log', $logs[0]);
        unlink(__DIR__.'/test-directory-for-logs/project/storage/logs/laravel.log');
    }
}


class StubForRemovingLinks extends Site
{
    function sitesPath()
    {
        return __DIR__.'/output';
    }
}
