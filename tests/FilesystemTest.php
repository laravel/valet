<?php

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\SetUpTearDownTrait;
use Valet\Filesystem;

class FilesystemTest extends TestCase
{
    use SetUpTearDownTrait;

    public function doTearDown()
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
        $this->assertFileExists(__DIR__.'/output/file.link');
        unlink(__DIR__.'/output/file.out');
        $files->removeBrokenLinksAt(__DIR__.'/output');
        $this->assertFileDoesNotExist(__DIR__.'/output/file.link');
    }
}
