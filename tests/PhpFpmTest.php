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

        // Passing specific version will change the .sock file
        resolve(StubForUpdatingFpmConfigFiles::class)->updateConfiguration('php@7.2');
        $contents = file_get_contents(__DIR__.'/output/fpm.conf');
        $this->assertStringContainsString(sprintf("\nuser = %s", user()), $contents);
        $this->assertStringContainsString("\ngroup = staff", $contents);
        $this->assertStringContainsString("\nlisten = ".VALET_HOME_PATH.'/valet72.sock', $contents);
    }

    public function test_it_can_generate_sock_file_name_from_php_version()
    {
        $this->assertEquals('valet72.sock', resolve(PhpFpm::class)->fpmSockName('php@7.2'));
        $this->assertEquals('valet72.sock', resolve(PhpFpm::class)->fpmSockName('php@72'));
        $this->assertEquals('valet72.sock', resolve(PhpFpm::class)->fpmSockName('php72'));
        $this->assertEquals('valet72.sock', resolve(PhpFpm::class)->fpmSockName('72'));
    }

    public function test_utilized_php_versions()
    {
        $fileSystemMock = Mockery::mock(Filesystem::class);
        $brewMock = Mockery::mock(Brew::class);

        $phpFpmMock = Mockery::mock(PhpFpm::class, [
            $brewMock,
            Mockery::mock(CommandLine::class),
            $fileSystemMock,
            resolve(Configuration::class),
            Mockery::mock(Site::class),
            Mockery::mock(Nginx::class),
        ])->makePartial();

        swap(PhpFpm::class, $phpFpmMock);

        $brewMock->shouldReceive('supportedPhpVersions')->andReturn(collect([
            'php@7.1',
            'php@7.2',
            'php@7.3',
            'php@7.4',
        ]));

        $brewMock->shouldReceive('getLinkedPhpFormula')->andReturn('php@7.3');

        $fileSystemMock->shouldReceive('scandir')
            ->once()
            ->with(VALET_HOME_PATH.'/Nginx')
            ->andReturn(['.gitkeep', 'isolated-site-71.test', 'isolated-site-72.test', 'isolated-site-73.test']);

        $fileSystemMock->shouldNotReceive('get')->with(VALET_HOME_PATH.'/Nginx/.gitkeep');

        $sites = [
            [
                'site' => 'isolated-site-71.test',
                'conf' => '# Valet isolated PHP version : 71'.PHP_EOL.'valet71.sock',
            ],
            [
                'site' => 'isolated-site-72.test',
                'conf' => '# Valet isolated PHP version : php@7.2'.PHP_EOL.'valet72.sock',
            ],
            [
                'site' => 'isolated-site-73.test',
                'conf' => '# Valet isolated PHP version : 73'.PHP_EOL.'valet.sock',
            ],
        ];

        foreach ($sites as $site) {
            $fileSystemMock->shouldReceive('get')->once()->with(VALET_HOME_PATH.'/Nginx/'.$site['site'])->andReturn($site['conf']);
        }

        $this->assertEquals(['php@7.1', 'php@7.2', 'php@7.3'], resolve(PhpFpm::class)->utilizedPhpVersions());
    }

    public function test_global_php_version_update_will_swap_socks()
    {
        $fileSystemMock = Mockery::mock(Filesystem::class);

        $phpFpmMock = Mockery::mock(PhpFpm::class, [
            Mockery::mock(Brew::class),
            Mockery::mock(CommandLine::class),
            $fileSystemMock,
            resolve(Configuration::class),
            Mockery::mock(Site::class),
            Mockery::mock(Nginx::class),
        ])->makePartial();

        swap(PhpFpm::class, $phpFpmMock);

        $fileSystemMock->shouldReceive('scandir')
            ->once()
            ->with(VALET_HOME_PATH.'/Nginx')
            ->andReturn([
                '.gitkeep',
                'isolated-site-71.test',
                'isolated-site-72.test',
                'isolated-site-73.test',
                'non-isolated-site.test',
            ]);

        // Skip dotfiles
        $fileSystemMock->shouldNotReceive('get')->with(VALET_HOME_PATH.'/Nginx/.gitkeep');

        // Any isolated site running on php72 would be replaced with default valet.sock,
        // as 72 will be the default version after the global PHP version switch
        $fileSystemMock->shouldReceive('get')
            ->once()
            ->with(VALET_HOME_PATH.'/Nginx/isolated-site-72.test')
            ->andReturn('# Valet isolated PHP version : 72'.PHP_EOL.'server { fastcgi_pass: valet72.sock }');

        $fileSystemMock->shouldReceive('put')->once()->withArgs([
            VALET_HOME_PATH.'/Nginx/isolated-site-72.test',
            '# Valet isolated PHP version : 72'.PHP_EOL.'server { fastcgi_pass: valet.sock }',
        ]);

        // Any isolated site running on current PHP version (with valet.sock),
        // should be still be running on the same version after the global version update
        $fileSystemMock->shouldReceive('get')
            ->once()
            ->with(VALET_HOME_PATH.'/Nginx/isolated-site-71.test')
            ->andReturn('# Valet isolated PHP version : 71'.PHP_EOL.'server { fastcgi_pass: valet.sock }');

        $fileSystemMock->shouldReceive('put')->once()->withArgs([
            VALET_HOME_PATH.'/Nginx/isolated-site-71.test',
            '# Valet isolated PHP version : 71'.PHP_EOL.'server { fastcgi_pass: valet71.sock }',
        ]);

        // PHP 7.3 sites won't be affected here
        $fileSystemMock->shouldReceive('get')
            ->once()
            ->with(VALET_HOME_PATH.'/Nginx/isolated-site-73.test')
            ->andReturn('# Valet isolated PHP version : 73'.PHP_EOL.'server { fastcgi_pass: valet73.sock }');

        $fileSystemMock->shouldNotReceive('put')->withArgs([
            VALET_HOME_PATH.'/Nginx/isolated-site-73.test',
            Mockery::any(),
        ]);

        // Nginx config that doesn't have the isolation header, It would not swap .sock files
        $fileSystemMock->shouldReceive('get')->once()->with(VALET_HOME_PATH.'/Nginx/non-isolated-site.test')->andReturn('valet.sock');
        $fileSystemMock->shouldNotReceive('put')->withArgs([
            VALET_HOME_PATH.'/Nginx/non-isolated-site.test',
            'valet71.sock',
        ]);

        // Switching from php7.1 to php7.2
        resolve(PhpFpm::class)->updateConfigurationForGlobalUpdate('php@7.2', 'php@7.1');
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
        $cliMock = Mockery::mock(CommandLine::class);
        $fileSystemMock = Mockery::mock(Filesystem::class);

        $phpFpmMock = Mockery::mock(PhpFpm::class, [
            $brewMock,
            $cliMock,
            $fileSystemMock,
            resolve(Configuration::class),
            $siteMock,
            $nginxMock,
        ])->makePartial();

        $phpFpmMock->shouldReceive('install');
        $cliMock->shouldReceive('quietly')->with('sudo rm '.VALET_HOME_PATH.'/valet*.sock')->once();
        $fileSystemMock->shouldReceive('unlink')->with(VALET_HOME_PATH.'/valet.sock')->once();

        $phpFpmMock->shouldReceive('updateConfiguration')->with('php@7.1')->once();
        $phpFpmMock->shouldReceive('updateConfigurationForGlobalUpdate')->withArgs(['php@7.2', 'php@7.1'])->once();

        $brewMock->shouldReceive('supportedPhpVersions')->andReturn(collect([
            'php@7.2',
            'php@5.6',
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

    public function test_use_version_with_site_parameter_will_isolate_a_site()
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
            'php@5.6',
        ]));

        $brewMock->shouldReceive('ensureInstalled')->with('php@7.2', [], $phpFpmMock->taps);
        $brewMock->shouldReceive('installed')->with('php@7.2');
        $brewMock->shouldReceive('determineAliasedVersion')->with('php@7.2')->andReturn('php@7.2');
        $brewMock->shouldReceive('linkedPhp')->once();

        $siteMock->shouldReceive('getSiteUrl')->with('test')->andReturn('test.test');
        $siteMock->shouldReceive('installSiteConfig')->withArgs(['test.test', 'valet72.sock', 'php@7.2']);
        $siteMock->shouldReceive('customPhpVersion')->with('test.test')->andReturn('72');

        $phpFpmMock->shouldReceive('stopIfUnused')->with('72')->once();
        $phpFpmMock->shouldReceive('updateConfiguration')->with('php@7.2')->once();
        $phpFpmMock->shouldReceive('restart')->with('php@7.2')->once();

        $nginxMock->shouldReceive('restart');

        // These should only run when doing global PHP switches
        $brewMock->shouldNotReceive('stopService');
        $brewMock->shouldNotReceive('link');
        $brewMock->shouldNotReceive('unlink');
        $phpFpmMock->shouldNotReceive('stopRunning');
        $phpFpmMock->shouldNotReceive('install');
        $phpFpmMock->shouldNotReceive('updateConfigurationForGlobalUpdate');

        $this->assertSame(null, $phpFpmMock->useVersion('php@7.2', false, 'test'));
    }

    public function test_use_version_can_remove_isolation_for_a_site()
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

        $this->assertSame(null, $phpFpmMock->useVersion('default', false, 'test'));
    }

    public function test_use_version_will_throw_if_site_is_not_parked_or_linked()
    {
        $siteMock = Mockery::mock(Site::class);

        $phpFpmMock = Mockery::mock(PhpFpm::class, [
            Mockery::mock(Brew::class),
            resolve(CommandLine::class),
            resolve(Filesystem::class),
            resolve(Configuration::class),
            $siteMock,
            Mockery::mock(Nginx::class),
        ])->makePartial();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("The [test] site could not be found in Valet's site list.");

        $siteMock->shouldReceive('getSiteUrl');

        $this->assertSame(null, $phpFpmMock->useVersion('default', false, 'test'));
    }
}

class StubForUpdatingFpmConfigFiles extends PhpFpm
{
    public function fpmConfigPath($phpVersion = null)
    {
        return __DIR__.'/output/fpm.conf';
    }
}
