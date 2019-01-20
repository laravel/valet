<?php

use Valet\Brew;
use Valet\PhpFpm;
use Valet\Filesystem;
use Valet\CommandLine;
use function Valet\user;
use function Valet\swap;
use function Valet\resolve;
use Illuminate\Container\Container;

class PhpFpmTest extends PHPUnit_Framework_TestCase
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

    public function test_fpm_is_configured_with_the_correct_user_group_and_port()
    {
        copy(__DIR__.'/files/fpm.conf', __DIR__.'/output/fpm.conf');
        mkdir(__DIR__.'/output/conf.d');
        copy(__DIR__.'/files/php-memory-limits.ini', __DIR__.'/output/conf.d/php-memory-limits.ini');
        resolve(StubForUpdatingFpmConfigFiles::class)->updateConfiguration();
        $contents = file_get_contents(__DIR__.'/output/fpm.conf');
        $this->assertContains(sprintf("\nuser = %s", user()), $contents);
        $this->assertContains("\ngroup = staff", $contents);
        $this->assertContains("\nlisten = ".VALET_HOME_PATH."/valet.sock", $contents);
    }

    public function test_stopRunning_will_pass_filtered_result_of_getRunningServices_to_stopService()
    {
        $brewMock = Mockery::mock(Brew::class);
        $brewMock->shouldReceive('getRunningServices')->once()
            ->andReturn(collect([
                'php7.2',
                'php@7.3',
                'php56',
                'php',
                'nginx',
                'somethingelse',
            ]));
        $brewMock->shouldReceive('stopService')->once()->with([
            'php7.2',
            'php@7.3',
            'php56',
            'php',
        ]);

        swap(Brew::class, $brewMock);
        resolve(PhpFpm::class)->stopRunning();
    }

    public function test_use_version_if_no_php_at_start_will_prefix()
    {
        $brewMock = Mockery::mock(Brew::class);
        $phpFpmMock = Mockery::mock(PhpFpm::class, [
            $brewMock,
            resolve(CommandLine::class),
            resolve(Filesystem::class),
        ])->makePartial();

        $phpFpmMock->shouldReceive('install');

        $brewMock->shouldReceive('search')->with('php7.2')->twice()->andReturn(collect([
            'php@7.2'
        ]));
        $brewMock->shouldReceive('supportedPhpVersions')->twice()->andReturn(collect([
            'php@7.2',
            'php@5.6',
        ]));
        $brewMock->shouldReceive('hasLinkedPhp')->andReturn(False);
        $brewMock->shouldReceive('ensureInstalled')->with('php@7.2');
        $brewMock->shouldReceive('link')->withArgs(['php@7.2', true]);

        // Test both non prefixed and prefixed
        $this->assertSame('php@7.2', $phpFpmMock->useVersion('7.2'));
        $this->assertSame('php@7.2', $phpFpmMock->useVersion('php7.2'));
    }

    /**
     * @expectedException DomainException
     * @expectedExceptionMessage Valet can't find a supported version of PHP for: php7.2
     */
    public function test_use_version_will_throw_if_searched_version_is_not_supported()
    {
        $brewMock = Mockery::mock(Brew::class);
        swap(Brew::class, $brewMock);

        $brewMock->shouldReceive('search')->with('php7.2')->andReturn(collect([
            'php@7.2'
        ]));
        $brewMock->shouldReceive('supportedPhpVersions')->andReturn(collect([
            'php@7.3',
            'php@7.1',
        ]));

        resolve(PhpFpm::class)->useVersion('7.2');
    }

    public function test_use_version_if_already_linked_php_will_unlink_before_installing()
    {
        $brewMock = Mockery::mock(Brew::class);
        $phpFpmMock = Mockery::mock(PhpFpm::class, [
            $brewMock,
            resolve(CommandLine::class),
            resolve(Filesystem::class),
        ])->makePartial();
        $phpFpmMock->shouldReceive('install');

        $brewMock->shouldReceive('search')->with('php@7.2')->andReturn(collect([
            'php@7.2'
        ]));
        $brewMock->shouldReceive('supportedPhpVersions')->andReturn(collect([
            'php@7.2',
            'php@5.6',
        ]));
        $brewMock->shouldReceive('hasLinkedPhp')->andReturn(true);
        $brewMock->shouldReceive('linkedPhp')->andReturn('php@7.1');
        $brewMock->shouldReceive('unlink')->with('php@7.1');
        $brewMock->shouldReceive('ensureInstalled')->with('php@7.2');
        $brewMock->shouldReceive('link')->withArgs(['php@7.2', true]);

        // Test both non prefixed and prefixed
        $this->assertSame('php@7.2', $phpFpmMock->useVersion('php@7.2'));
    }
}


class StubForUpdatingFpmConfigFiles extends PhpFpm
{
    function fpmConfigPath()
    {
        return __DIR__.'/output/fpm.conf';
    }
}
