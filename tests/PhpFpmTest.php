<?php

use Illuminate\Container\Container;
use Valet\Brew;
use Valet\CommandLine;
use Valet\Configuration;
use Valet\Filesystem;
use Valet\Nginx;
use Valet\PhpFpm;
use function Valet\resolve;
use Valet\Site;
use function Valet\swap;
use function Valet\user;

class PhpFpmTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
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

    public function test_fpm_is_configured_with_the_correct_user_group_and_port()
    {
        copy(__DIR__.'/files/fpm.conf', __DIR__.'/output/fpm.conf');
        mkdir(__DIR__.'/output/conf.d');
        copy(__DIR__.'/files/php-memory-limits.ini', __DIR__.'/output/conf.d/php-memory-limits.ini');
        resolve(StubForUpdatingFpmConfigFiles::class)->updateConfiguration();
        $contents = file_get_contents(__DIR__.'/output/fpm.conf');
        $this->assertStringContainsString(sprintf("\nuser = %s", user()), $contents);
        $this->assertStringContainsString("\ngroup = staff", $contents);
        $this->assertStringContainsString("\nlisten = ".VALET_HOME_PATH.'/valet.sock', $contents);
    }

    public function test_stopRunning_will_pass_filtered_result_of_getRunningServices_to_stopService()
    {
        $brewMock = Mockery::mock(Brew::class);
        $brewMock->shouldReceive('getAllRunningServices')->once()
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

    public function test_use_version_will_convert_passed_php_version()
    {
        $brewMock = Mockery::mock(Brew::class);
        $nginxMock = Mockery::mock(Nginx::class);
        $siteMock = Mockery::mock(Site::class);

        $phpFpmMock = Mockery::mock(PhpFpm::class, [
            $brewMock,
            resolve(CommandLine::class),
            resolve(Filesystem::class),
            resolve(Configuration::class),
            $siteMock,
            $nginxMock,
        ])->makePartial();

        $phpFpmMock->shouldReceive('install');

        $brewMock->shouldReceive('supportedPhpVersions')->twice()->andReturn(collect([
            'php@7.2',
            'php@5.6',
        ]));
        $brewMock->shouldReceive('hasLinkedPhp')->andReturn(false);
        $brewMock->shouldReceive('ensureInstalled')->with('php@7.2', [], $phpFpmMock->taps);
        $brewMock->shouldReceive('determineAliasedVersion')->with('php@7.2')->andReturn('php@7.2');
        $brewMock->shouldReceive('link')->withArgs(['php@7.2', true]);
        $brewMock->shouldReceive('linkedPhp');
        $brewMock->shouldReceive('installed');
        $brewMock->shouldReceive('getAllRunningServices')->andReturn(collect());
        $brewMock->shouldReceive('stopService');

        $nginxMock->shouldReceive('restart');

        // Test both non prefixed and prefixed
        $this->assertSame('php@7.2', $phpFpmMock->useVersion('php7.2'));
        $this->assertSame('php@7.2', $phpFpmMock->useVersion('php72'));
    }

    public function test_use_version_will_throw_if_version_not_supported()
    {
        $this->expectException(DomainException::class);

        $brewMock = Mockery::mock(Brew::class);
        swap(Brew::class, $brewMock);

        $brewMock->shouldReceive('supportedPhpVersions')->andReturn(collect([
            'php@7.3',
            'php@7.1',
        ]));

        resolve(PhpFpm::class)->useVersion('php@7.2');
    }

    public function test_use_version_if_already_linked_php_will_unlink_before_installing()
    {
        $brewMock = Mockery::mock(Brew::class);
        $nginxMock = Mockery::mock(Nginx::class);
        $siteMock = Mockery::mock(Site::class);

        $phpFpmMock = Mockery::mock(PhpFpm::class, [
            $brewMock,
            resolve(CommandLine::class),
            resolve(Filesystem::class),
            resolve(Configuration::class),
            $siteMock,
            $nginxMock,
        ])->makePartial();

        $phpFpmMock->shouldReceive('install');
        $phpFpmMock->shouldReceive('updateConfigurationForGlobalUpdate');

        $brewMock->shouldReceive('supportedPhpVersions')->andReturn(collect([
            'php@7.2',
            'php@5.6',
        ]));
        $brewMock->shouldReceive('hasLinkedPhp')->andReturn(true);
        $brewMock->shouldReceive('getLinkedPhpFormula')->andReturn('php@7.1');
        $brewMock->shouldReceive('unlink')->with('php@7.1');
        $brewMock->shouldReceive('ensureInstalled')->with('php@7.2', [], $phpFpmMock->taps);
        $brewMock->shouldReceive('determineAliasedVersion')->with('php@7.2')->andReturn('php@7.2');
        $brewMock->shouldReceive('link')->withArgs(['php@7.2', true]);
        $brewMock->shouldReceive('linkedPhp');
        $brewMock->shouldReceive('installed');
        $brewMock->shouldReceive('getAllRunningServices')->andReturn(collect());
        $brewMock->shouldReceive('stopService');

        $nginxMock->shouldReceive('restart');

        // Test both non prefixed and prefixed
        $this->assertSame('php@7.2', $phpFpmMock->useVersion('php@7.2'));
    }
}

class StubForUpdatingFpmConfigFiles extends PhpFpm
{
    public function fpmConfigPath($phpVersion = null)
    {
        return __DIR__.'/output/fpm.conf';
    }
}
