<?php

use Valet\Filesystem;
use Valet\WebContext;

final class WebContextTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
{
    public function tear_down()
    {
        Mockery::close();
    }

    public function test_it_can_guess_correctly_web_context()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('exists')->once()->with('/not-exits/bin/brew')->andReturn(true);
        $files->shouldReceive('isDir')->once()->with('/not-exits/Cellar')->andReturn(true);

        $webContext = new WebContext($files);

        $found = $webContext->guessHomebrewPath('/not-exits/');

        $this->assertContains('a', ['a', 'b', 'c']);
    }
}
