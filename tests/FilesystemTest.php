<?php

use Valet\Filesystem;

class FilesystemTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        exec('rm -rf '.__DIR__.'/output');
        mkdir(__DIR__.'/output');
        touch(__DIR__.'/output/.gitkeep');
    }


    public function test_remove_broken_links_removes_broken_symlinks()
    {
        $files = new Filesystem;
        file_put_contents(__DIR__.'/output/file.out', 'test');
        symlink(__DIR__.'/output/file.out', __DIR__.'/output/file.link');
        $this->assertTrue(file_exists(__DIR__.'/output/file.link'));
        unlink(__DIR__.'/output/file.out');
        $files->removeBrokenLinksAt(__DIR__.'/output');
        $this->assertFalse(file_exists(__DIR__.'/output/file.link'));
    }
}
