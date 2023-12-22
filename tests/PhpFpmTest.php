<?php

use Illuminate\Container\Container;
use Valet\Brew;
use Valet\CommandLine;
use Valet\Configuration;
use Valet\Filesystem;
use Valet\Nginx;
use Valet\PhpFpm;
use Valet\Site;

use function Valet\resolve;
use function Valet\swap;
use function Valet\user;

class PhpFpmTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
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

    public function test_fpm_is_configured_with_the_correct_user_group_and_port()
    {
        copy(__DIR__.'/files/fpm.conf', __DIR__.'/output/fpm.conf');
        copy(__DIR__.'/files/fpm.conf', __DIR__.'/output/www.conf');
        mkdir(__DIR__.'/output/conf.d');
        copy(__DIR__.'/files/php-memory-limits.ini', __DIR__.'/output/conf.d/php-memory-limits.ini');

        resolve(StubForUpdatingFpmConfigFiles::class)->createConfigurationFiles('php@7.2');
        $contents = file_get_contents(__DIR__.'/output/fpm.conf');
        $this->assertStringContainsString(sprintf(PHP_EOL.'user = %s', user()), $contents);
        $this->assertStringContainsString(PHP_EOL.'group = staff', $contents);
        $this->assertStringContainsString(PHP_EOL.'listen = '.VALET_HOME_PATH.'/valet72.sock', $contents);

        // It should disable old or default FPM Pool configuration
        $this->assertFileDoesNotExist(__DIR__.'/output/www.conf');
        $this->assertFileExists(__DIR__.'/output/www.conf-backup');
    }

    public function test_it_can_generate_sock_file_name_from_php_version()
    {
        $this->assertEquals('valet72.sock', resolve(PhpFpm::class)->fpmSockName('php@7.2'));
        $this->assertEquals('valet72.sock', resolve(PhpFpm::class)->fpmSockName('php@72'));
        $this->assertEquals('valet72.sock', resolve(PhpFpm::class)->fpmSockName('php72'));
        $this->assertEquals('valet72.sock', resolve(PhpFpm::class)->fpmSockName('72'));
    }

    public function test_it_normalizes_php_versions()
    {
        $this->assertEquals('php@8.1', resolve(PhpFpm::class)->normalizePhpVersion('php@8.1'));
        $this->assertEquals('php@8.1', resolve(PhpFpm::class)->normalizePhpVersion('php8.1'));
        $this->assertEquals('php@8.1', resolve(PhpFpm::class)->normalizePhpVersion('php81'));
        $this->assertEquals('php@8.1', resolve(PhpFpm::class)->normalizePhpVersion('8.1'));
        $this->assertEquals('php@8.1', resolve(PhpFpm::class)->normalizePhpVersion('81'));
        $this->assertEquals('', resolve(PhpFpm::class)->normalizePhpVersion(''));
        $this->assertEquals('', resolve(PhpFpm::class)->normalizePhpVersion(null));
    }

    public function test_it_validates_php_versions_when_installed()
    {
        $brewMock = Mockery::mock(Brew::class);

        $brewMock->shouldReceive('supportedPhpVersions')->andReturn(collect(['php@7.4']));
        $brewMock->shouldReceive('determineAliasedVersion')->andReturn('7.4');

        swap(Brew::class, $brewMock);

        $this->assertEquals('php@7.4', resolve(PhpFpm::class)->validateRequestedVersion('7.4'));
    }

    public function test_it_validates_php_versions_when_uninstalled()
    {
        $brewMock = Mockery::mock(Brew::class);

        $brewMock->shouldReceive('supportedPhpVersions')->andReturn(collect(['php@7.4']));
        $brewMock->shouldReceive('determineAliasedVersion')->andReturn('ERROR - NO BREW ALIAS FOUND');

        swap(Brew::class, $brewMock);

        $this->assertEquals('php@7.4', resolve(PhpFpm::class)->validateRequestedVersion('7.4'));
    }

    public function test_it_throws_when_validating_invalid_php()
    {
        $this->expectException(DomainException::class);

        $brewMock = Mockery::mock(Brew::class);

        $brewMock->shouldReceive('supportedPhpVersions')->andReturn(collect(['php@7.4']));
        $brewMock->shouldReceive('determineAliasedVersion')->andReturn('ERROR - NO BREW ALIAS FOUND');

        swap(Brew::class, $brewMock);

        $this->assertEquals('php@7.4', resolve(PhpFpm::class)->validateRequestedVersion('9.1'));
    }

    public function test_utilized_php_versions()
    {
        $brewMock = Mockery::mock(Brew::class);
        $nginxMock = Mockery::mock(Nginx::class);
        $fileSystemMock = Mockery::mock(Filesystem::class);

        $brewMock->shouldReceive('supportedPhpVersions')->andReturn(collect([
            'php@7.1',
            'php@7.2',
            'php@7.3',
            'php@7.4',
        ]));

        $brewMock->shouldReceive('getLinkedPhpFormula')->andReturn('php@7.3');

        $nginxMock->shouldReceive('configuredSites')
            ->once()
            ->andReturn(collect(['isolated-site-71.test', 'isolated-site-72.test', 'isolated-site-73.test']));

        $sites = [
            [
                'site' => 'isolated-site-71.test',
                'conf' => '# '.ISOLATED_PHP_VERSION.'=71'.PHP_EOL.'valet71.sock',
            ],
            [
                'site' => 'isolated-site-72.test',
                'conf' => '# '.ISOLATED_PHP_VERSION.'=php@7.2'.PHP_EOL.'valet72.sock',
            ],
            [
                'site' => 'isolated-site-73.test',
                'conf' => '# '.ISOLATED_PHP_VERSION.'=73'.PHP_EOL.'valet.sock',
            ],
        ];

        foreach ($sites as $site) {
            $fileSystemMock->shouldReceive('get')->once()->with(VALET_HOME_PATH.'/Nginx/'.$site['site'])->andReturn($site['conf']);
        }

        swap(Filesystem::class, $fileSystemMock);
        swap(Brew::class, $brewMock);
        swap(Nginx::class, $nginxMock);

        $this->assertEquals(['php@7.1', 'php@7.2', 'php@7.3'], resolve(PhpFpm::class)->utilizedPhpVersions());
    }

    public function test_it_lists_isolated_directories()
    {
        $nginxMock = Mockery::mock(Nginx::class);
        $siteMock = Mockery::mock(Site::class);
        $fileSystemMock = Mockery::mock(Filesystem::class);

        $nginxMock->shouldReceive('configuredSites')
            ->once()
            ->andReturn(collect(['isolated-site-71.test', 'isolated-site-72.test', 'not-isolated-site.test']));

        $siteMock->shouldReceive('customPhpVersion')->with('isolated-site-71.test')->andReturn('71');
        $siteMock->shouldReceive('customPhpVersion')->with('isolated-site-72.test')->andReturn('72');
        $siteMock->shouldReceive('normalizePhpVersion')->with('71')->andReturn('php@7.1');
        $siteMock->shouldReceive('normalizePhpVersion')->with('72')->andReturn('php@7.2');

        $sites = [
            [
                'site' => 'isolated-site-71.test',
                'conf' => '# '.ISOLATED_PHP_VERSION.'=71'.PHP_EOL.'valet71.sock',
            ],
            [
                'site' => 'isolated-site-72.test',
                'conf' => '# '.ISOLATED_PHP_VERSION.'=php@7.2'.PHP_EOL.'valet72.sock',
            ],
            [
                'site' => 'not-isolated-site.test',
                'conf' => 'This one is not isolated',
            ],
        ];

        foreach ($sites as $site) {
            $fileSystemMock->shouldReceive('get')->once()->with(VALET_HOME_PATH.'/Nginx/'.$site['site'])->andReturn($site['conf']);
        }

        swap(Nginx::class, $nginxMock);
        swap(Site::class, $siteMock);
        swap(Filesystem::class, $fileSystemMock);

        $this->assertEquals([
            [
                'url' => 'isolated-site-71.test',
                'version' => 'php@7.1',
            ],
            [
                'url' => 'isolated-site-72.test',
                'version' => 'php@7.2',
            ],
        ], resolve(PhpFpm::class)->isolatedDirectories()->toArray());
    }

    public function test_stop_unused_php_versions()
    {
        $brewMock = Mockery::mock(Brew::class);

        $phpFpmMock = Mockery::mock(PhpFpm::class, [
            $brewMock,
            Mockery::mock(CommandLine::class),
            Mockery::mock(Filesystem::class),
            resolve(Configuration::class),
            Mockery::mock(Site::class),
            Mockery::mock(Nginx::class),
        ])->makePartial();

        swap(PhpFpm::class, $phpFpmMock);

        $phpFpmMock->shouldReceive('utilizedPhpVersions')->andReturn([
            'php@7.1',
            'php@7.2',
        ]);

        // Would do nothing
        resolve(PhpFpm::class)->stopIfUnused(null);

        // This currently-un-used PHP version should be stopped
        $brewMock->shouldReceive('stopService')->times(3)->with('php@7.3');
        resolve(PhpFpm::class)->stopIfUnused('73');
        resolve(PhpFpm::class)->stopIfUnused('php73');
        resolve(PhpFpm::class)->stopIfUnused('php@7.3');

        // These currently-used PHP versions should not be stopped
        $brewMock->shouldNotReceive('stopService')->with('php@7.1');
        $brewMock->shouldNotReceive('stopService')->with('php@7.2');
        resolve(PhpFpm::class)->stopIfUnused('php@7.1');
        resolve(PhpFpm::class)->stopIfUnused('php@7.2');
    }

    public function test_stopRunning_will_pass_filtered_result_of_getRunningServices_to_stopService()
    {
        $brewMock = Mockery::mock(Brew::class);
        $brewMock->shouldReceive('getAllRunningServices')->once()
            ->andReturn(collect([
                'php7.2',
                'php@7.3',
                'php71',
                'php',
                'nginx',
                'somethingelse',
            ]));
        $brewMock->shouldReceive('stopService')->once()->with([
            'php7.2',
            'php@7.3',
            'php71',
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
        $filesystem = Mockery::mock(Filesystem::class);
        $cli = Mockery::mock(CommandLine::class);

        $phpFpmMock = Mockery::mock(PhpFpm::class, [
            $brewMock,
            $cli,
            $filesystem,
            resolve(Configuration::class),
            $siteMock,
            $nginxMock,
        ])->makePartial();

        $phpFpmMock->shouldReceive('install');

        $brewMock->shouldReceive('supportedPhpVersions')->twice()->andReturn(collect([
            'php@7.2',
            'php@7.1',
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

        $filesystem->shouldReceive('unlink')->with(VALET_HOME_PATH.'/valet.sock');

        $cli->shouldReceive('quietly')->with('sudo rm '.VALET_HOME_PATH.'/valet.sock');

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

        $brewMock->shouldReceive('supportedPhpVersions')->andReturn(collect([
            'php@7.2',
            'php@7.1',
        ]));

        $brewMock->shouldReceive('hasLinkedPhp')->andReturn(true);
        $brewMock->shouldReceive('linkedPhp')->andReturn('php@7.1');
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

        $this->assertSame('php@7.2', $phpFpmMock->useVersion('php@7.2'));
    }

    public function test_isolate_will_isolate_a_site()
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

        $brewMock->shouldReceive('supportedPhpVersions')->andReturn(collect([
            'php@7.2',
            'php@7.1',
        ]));

        $brewMock->shouldReceive('ensureInstalled')->with('php@7.2', [], $phpFpmMock->taps);
        $brewMock->shouldReceive('installed')->with('php@7.2');
        $brewMock->shouldReceive('determineAliasedVersion')->with('php@7.2')->andReturn('php@7.2');
        // $brewMock->shouldReceive('linkedPhp')->once();

        $siteMock->shouldReceive('getSiteUrl')->with('test')->andReturn('test.test');
        $siteMock->shouldReceive('isolate')->withArgs(['test.test', 'php@7.2']);
        $siteMock->shouldReceive('customPhpVersion')->with('test.test')->andReturn('72');

        $phpFpmMock->shouldReceive('stopIfUnused')->with('72')->once();
        $phpFpmMock->shouldReceive('createConfigurationFiles')->with('php@7.2')->once();
        $phpFpmMock->shouldReceive('restart')->with('php@7.2')->once();

        $nginxMock->shouldReceive('restart');

        // These should only run when doing global PHP switches
        $brewMock->shouldNotReceive('stopService');
        $brewMock->shouldNotReceive('link');
        $brewMock->shouldNotReceive('unlink');
        $phpFpmMock->shouldNotReceive('stopRunning');
        $phpFpmMock->shouldNotReceive('install');

        $this->assertSame(null, $phpFpmMock->isolateDirectory('test', 'php@7.2'));
    }

    public function test_un_isolate_will_remove_isolation_for_a_site()
    {
        $nginxMock = Mockery::mock(Nginx::class);
        $siteMock = Mockery::mock(Site::class);

        $phpFpmMock = Mockery::mock(PhpFpm::class, [
            Mockery::mock(Brew::class),
            resolve(CommandLine::class),
            resolve(Filesystem::class),
            resolve(Configuration::class),
            $siteMock,
            $nginxMock,
        ])->makePartial();

        $siteMock->shouldReceive('getSiteUrl')->with('test')->andReturn('test.test');
        $siteMock->shouldReceive('customPhpVersion')->with('test.test')->andReturn('74');
        $siteMock->shouldReceive('removeIsolation')->with('test.test')->once();
        $phpFpmMock->shouldReceive('stopIfUnused')->with('74');
        $nginxMock->shouldReceive('restart');

        $this->assertSame(null, $phpFpmMock->unIsolateDirectory('test'));
    }

    public function test_isolate_will_throw_if_site_is_not_parked_or_linked()
    {
        $brewMock = Mockery::mock(Brew::class);
        $brewMock->shouldReceive('linkedPhp')->andReturn('php@7.4');

        $configMock = Mockery::mock(Configuration::class);
        $configMock->shouldReceive('read')->andReturn(['tld' => 'jamble', 'paths' => []]);

        swap(Brew::class, $brewMock);
        swap(Nginx::class, Mockery::mock(Nginx::class));
        swap(Configuration::class, $configMock);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("The [test] site could not be found in Valet's site list.");

        resolve(PhpFpm::class)->isolateDirectory('test', 'php@8.1');
    }
}

class StubForUpdatingFpmConfigFiles extends PhpFpm
{
    public function fpmConfigPath(?string $phpVersion = null): string
    {
        return __DIR__.'/output/fpm.conf';
    }
}
