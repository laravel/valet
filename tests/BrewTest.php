<?php

use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Valet\Brew;
use Valet\CommandLine;
use Valet\Filesystem;
use function Valet\resolve;
use function Valet\swap;
use function Valet\user;

class BrewTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
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
        Mockery::close();
    }

    public function test_brew_can_be_resolved_from_container()
    {
        $this->assertInstanceOf(Brew::class, resolve(Brew::class));
    }

    public function test_installed_returns_true_when_given_formula_is_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew info php@8.2 --json=v2')
        ->andReturn('{"formulae":[{"name":"php@8.2","full_name":"php@8.2","aliases":[],"versioned_formulae":[],"versions":{"stable":"8.2.5"},"installed":[{"version":"8.2.5"}]}]}');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(Brew::class)->installed('php@8.2'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew info php --json=v2')
        ->andReturn('{"formulae":[{"name":"php","full_name":"php","aliases":["php@8.0"],"versioned_formulae":[],"versions":{"stable":"8.0.0"},"installed":[{"version":"8.0.0"}]}]}');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(Brew::class)->installed('php'));
    }

    public function test_installed_returns_true_when_given_cask_formula_is_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew info ngrok --json=v2')
            ->andReturn('{"casks":[{"name":"ngrok","full_name":"ngrok","aliases":[],"versioned_formulae":[],"versions":{"stable":"8.2.5"},"installed":[{"version":"8.2.5"}]}]}');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(Brew::class)->installed('ngrok'));
    }

    public function test_installed_returns_false_when_given_formula_is_not_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew info php@8.2 --json=v2')->andReturn('');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Brew::class)->installed('php@8.2'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew info php@8.2 --json=v2')->andReturn('Error: No formula found');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Brew::class)->installed('php@8.2'));
    }

    public function test_has_installed_php_indicates_if_php_is_installed_via_brew()
    {
        $brew = Mockery::mock(Brew::class.'[installedPhpFormulae]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedPhpFormulae')->andReturn(collect(['php@5.5']));
        $this->assertFalse($brew->hasInstalledPhp());

        $brew = Mockery::mock(Brew::class.'[installedPhpFormulae]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedPhpFormulae')->andReturn(collect(['php@8.2']));
        $this->assertTrue($brew->hasInstalledPhp());

        $brew = Mockery::mock(Brew::class.'[installedPhpFormulae]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedPhpFormulae')->andReturn(collect(['php@8.1']));
        $this->assertTrue($brew->hasInstalledPhp());

        $brew = Mockery::mock(Brew::class.'[installedPhpFormulae]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedPhpFormulae')->andReturn(collect(['php@8.0']));
        $this->assertTrue($brew->hasInstalledPhp());

        $brew = Mockery::mock(Brew::class.'[installedPhpFormulae]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedPhpFormulae')->andReturn(collect(['php@8.2']));
        $this->assertTrue($brew->hasInstalledPhp());

        $brew = Mockery::mock(Brew::class.'[installedPhpFormulae]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedPhpFormulae')->andReturn(collect(['php@8.2']));
        $this->assertTrue($brew->hasInstalledPhp());

        $brew = Mockery::mock(Brew::class.'[installedPhpFormulae]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedPhpFormulae')->andReturn(collect(['php@8.2', 'php82']));
        $this->assertTrue($brew->hasInstalledPhp());

        $brew = Mockery::mock(Brew::class.'[installedPhpFormulae]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedPhpFormulae')->andReturn(collect(['php81', 'php@8.1']));
        $this->assertTrue($brew->hasInstalledPhp());

        $brew = Mockery::mock(Brew::class.'[installedPhpFormulae]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedPhpFormulae')->andReturn(collect(['php@8.0']));
        $this->assertTrue($brew->hasInstalledPhp());
    }

    public function test_tap_taps_the_given_homebrew_repository()
    {
        $cli = Mockery::mock(CommandLine::class);
        $prefix = Brew::BREW_DISABLE_AUTO_CLEANUP;
        $cli->shouldReceive('passthru')->once()->with($prefix.' sudo -u "'.user().'" brew tap php@8.2');
        $cli->shouldReceive('passthru')->once()->with($prefix.' sudo -u "'.user().'" brew tap php@8.1');
        $cli->shouldReceive('passthru')->once()->with($prefix.' sudo -u "'.user().'" brew tap php@8.0');
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->tap('php@8.2', 'php@8.1', 'php@8.0');
    }

    public function test_restart_restarts_the_service_using_homebrew_services()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew info dnsmasq --json=v2')->andReturn('{"formulae":[{"name":"dnsmasq","full_name":"dnsmasq","aliases":[],"versioned_formulae":[],"versions":{"stable":"1"},"installed":[{"version":"1"}]}]}');
        $cli->shouldReceive('quietly')->once()->with('brew services stop dnsmasq');
        $cli->shouldReceive('quietly')->once()->with('sudo brew services stop dnsmasq');
        $cli->shouldReceive('quietly')->once()->with('sudo brew services start dnsmasq');
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->restartService('dnsmasq');
    }

    public function test_stop_stops_the_service_using_homebrew_services()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew info dnsmasq --json=v2')->andReturn('{"formulae":[{"name":"dnsmasq","full_name":"dnsmasq","aliases":[],"versioned_formulae":[],"versions":{"stable":"1"},"installed":[{"version":"1"}]}]}');
        $cli->shouldReceive('quietly')->once()->with('brew services stop dnsmasq');
        $cli->shouldReceive('quietly')->once()->with('sudo brew services stop dnsmasq');
        $cli->shouldReceive('quietly')->once()->with('sudo chown -R '.user().":admin '".BREW_PREFIX."/Cellar/dnsmasq'");
        $cli->shouldReceive('quietly')->once()->with('sudo chown -R '.user().":admin '".BREW_PREFIX."/opt/dnsmasq'");
        $cli->shouldReceive('quietly')->once()->with('sudo chown -R '.user().":admin '".BREW_PREFIX."/var/homebrew/linked/dnsmasq'");
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->stopService('dnsmasq');
    }

    public function test_linked_php_returns_linked_php_formula_name()
    {
        $getBrewMock = function ($filesystem) {
            $brewMock = Mockery::mock(Brew::class, [new CommandLine, $filesystem])->makePartial();
            $brewMock->shouldReceive('hasLinkedPhp')->once()->andReturn(true);

            return $brewMock;
        };

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('readLink')->once()->with(BREW_PREFIX.'/bin/php')->andReturn('/test/path/php/8.0.0/test');
        $this->assertSame('php@8.0', $getBrewMock($files)->linkedPhp());

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('readLink')->once()->with(BREW_PREFIX.'/bin/php')->andReturn('/test/path/php/8.1.0/test');
        $this->assertSame('php@8.1', $getBrewMock($files)->linkedPhp());

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('readLink')->once()->with(BREW_PREFIX.'/bin/php')->andReturn('/test/path/php@8.2/8.2.13/test');
        $this->assertSame('php@8.2', $getBrewMock($files)->linkedPhp());

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('readLink')->once()->with(BREW_PREFIX.'/bin/php')->andReturn('/test/path/php/8.2.9_2/test');
        $this->assertSame('php@8.2', $getBrewMock($files)->linkedPhp());

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('readLink')->once()->with(BREW_PREFIX.'/bin/php')->andReturn('/test/path/php81/8.1.9_2/test');
        $this->assertSame('php@8.1', $getBrewMock($files)->linkedPhp());
    }

    public function test_linked_php_throws_exception_if_no_php_link()
    {
        $this->expectException(DomainException::class);

        $brewMock = Mockery::mock(Brew::class)->makePartial();
        $brewMock->shouldReceive('hasLinkedPhp')->once()->andReturn(false);
        $brewMock->linkedPhp();
    }

    public function test_has_linked_php_returns_true_if_php_link_exists()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isLink')->twice()->with(BREW_PREFIX.'/bin/php')->andReturn(false, true);
        swap(Filesystem::class, $files);
        $brew = resolve(Brew::class);

        $this->assertFalse($brew->hasLinkedPhp());
        $this->assertTrue($brew->hasLinkedPhp());
    }

    public function test_linked_php_throws_exception_if_unsupported_php_version_is_linked()
    {
        $this->expectException(DomainException::class);

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isLink')->once()->with(BREW_PREFIX.'/bin/php')->andReturn(true);
        $files->shouldReceive('readLink')->once()->with(BREW_PREFIX.'/bin/php')->andReturn('/test/path/php/5.4.14/test');
        swap(Filesystem::class, $files);
        resolve(Brew::class)->linkedPhp();
    }

    public function test_install_or_fail_will_install_brew_formulae()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with(Brew::BREW_DISABLE_AUTO_CLEANUP.' brew install dnsmasq', Mockery::type('Closure'));
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->installOrFail('dnsmasq');
    }

    public function test_install_or_fail_can_install_taps()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with(Brew::BREW_DISABLE_AUTO_CLEANUP.' brew install dnsmasq', Mockery::type('Closure'));
        swap(CommandLine::class, $cli);
        $brew = Mockery::mock(Brew::class.'[tap]', [$cli, new Filesystem]);
        $brew->shouldReceive('tap')->once()->with(['test/tap']);
        $brew->installOrFail('dnsmasq', [], ['test/tap']);
    }

    public function test_install_or_fail_throws_exception_on_failure()
    {
        $this->expectException(DomainException::class);

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->andReturnUsing(function ($command, $onError) {
            $onError(1, 'test error ouput');
        });
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->installOrFail('dnsmasq');
    }

    public function test_link_will_throw_exception_on_failure()
    {
        $this->expectException(DomainException::class);

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->withArgs([
            'brew link aformula',
            Mockery::type('callable'),
        ])->andReturnUsing(function ($command, $onError) {
            $onError(1, 'test error output');
        });
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->link('aformula');
    }

    public function test_link_will_pass_formula_to_run_as_user()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->withArgs([
            'brew link aformula',
            Mockery::type('callable'),
        ])->andReturn('Some output');

        swap(CommandLine::class, $cli);
        $this->assertSame('Some output', resolve(Brew::class)->link('aformula'));
    }

    public function test_link_will_pass_formula_and_force_to_run_as_user_if_set()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->withArgs([
            'brew link aformula --force',
            Mockery::type('callable'),
        ])->andReturn('Some output forced');

        swap(CommandLine::class, $cli);
        $this->assertSame('Some output forced', resolve(Brew::class)->link('aformula', true));
    }

    public function test_unlink_will_throw_exception_on_failure()
    {
        $this->expectException(DomainException::class);

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->withArgs([
            'brew unlink aformula',
            Mockery::type('callable'),
        ])->andReturnUsing(function ($command, $onError) {
            $onError(1, 'test error output');
        });
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->unlink('aformula');
    }

    public function test_unlink_will_pass_formula_to_run_as_user()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->withArgs([
            'brew unlink aformula',
            Mockery::type('callable'),
        ])->andReturn('Some output');

        swap(CommandLine::class, $cli);
        $this->assertSame('Some output', resolve(Brew::class)->unlink('aformula'));
    }

    public function test_getRunningServices_will_throw_exception_on_failure()
    {
        $this->expectException(DomainException::class);

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->withArgs([
            'brew services list | grep started | awk \'{ print $1; }\'',
            Mockery::type('callable'),
        ])->andReturnUsing(function ($command, $onError) {
            $onError(1, 'test error output');
        });
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->getRunningServices(true);
    }

    public function test_getRunningServices_will_pass_to_brew_services_list_and_return_array()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->withArgs([
            'brew services list | grep started | awk \'{ print $1; }\'',
            Mockery::type('callable'),
        ])->andReturn('service1'.PHP_EOL.'service2'.PHP_EOL.PHP_EOL.'service3'.PHP_EOL);

        swap(CommandLine::class, $cli);
        $result = resolve(Brew::class)->getRunningServices(true);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame([
            'service1',
            'service2',
            'service3',
        ], array_values($result->all()));
    }

    public function test_getAllRunningServices_will_return_both_root_and_user_services()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->withArgs([
            'sudo brew services list | grep started | awk \'{ print $1; }\'',
            Mockery::type('callable'),
        ])->andReturn('sudo_ran_service');
        $cli->shouldReceive('runAsUser')->once()->withArgs([
            'brew services list | grep started | awk \'{ print $1; }\'',
            Mockery::type('callable'),
        ])->andReturn('user_ran_service');

        swap(CommandLine::class, $cli);
        $result = resolve(Brew::class)->getAllRunningServices();
        $this->assertSame([
            'sudo_ran_service',
            'user_ran_service',
        ], array_values($result->all()));
    }

    public function test_getAllRunningServices_will_return_unique_services()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->andReturn('service1'.PHP_EOL.'service2'.PHP_EOL.'service1'.PHP_EOL);
        $cli->shouldReceive('runAsUser')->once()->andReturn('service3'.PHP_EOL.'service4'.PHP_EOL.'service2'.PHP_EOL);

        swap(CommandLine::class, $cli);
        $result = resolve(Brew::class)->getAllRunningServices();
        $this->assertSame([
            'service1',
            'service2',
            'service3',
            'service4',
        ], array_values($result->all()));
    }

    /**
     * @dataProvider supportedPhpLinkPathProvider
     */
    public function test_get_parsed_linked_php_will_return_matches_for_linked_php($path, $matches)
    {
        $getBrewMock = function ($filesystem) {
            $brewMock = Mockery::mock(Brew::class, [new CommandLine, $filesystem])->makePartial();
            $brewMock->shouldReceive('hasLinkedPhp')->once()->andReturn(true);

            return $brewMock;
        };

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('readLink')->once()->with(BREW_PREFIX.'/bin/php')->andReturn($path);
        $this->assertSame($matches, $getBrewMock($files)->getParsedLinkedPhp());
    }

    /**
     * @dataProvider supportedPhpLinkPathProvider
     */
    public function test_get_linked_php_formula_will_return_linked_php_directory($path, $matches, $expectedLinkFormula)
    {
        $brewMock = Mockery::mock(Brew::class)->makePartial();
        $brewMock->shouldReceive('getParsedLinkedPhp')->andReturn($matches);

        $this->assertSame($expectedLinkFormula, $brewMock->getLinkedPhpFormula());
    }

    public function test_restart_linked_php_will_pass_through_linked_php_formula_to_restart_service()
    {
        $brewMock = Mockery::mock(Brew::class)->makePartial();
        $brewMock->shouldReceive('getLinkedPhpFormula')->once()->andReturn('php@8.2-test');
        $brewMock->shouldReceive('restartService')->once()->with('php@8.2-test');
        $brewMock->restartLinkedPhp();
    }

    public function test_it_can_get_php_binary_path_from_php_version()
    {
        // Check the default `/opt/homebrew/opt/php@8.1/bin/php` location first
        $brewMock = Mockery::mock(Brew::class, [
            Mockery::mock(CommandLine::class),
            $files = Mockery::mock(Filesystem::class),
        ])->makePartial();

        $files->shouldReceive('exists')->once()->with(BREW_PREFIX.'/opt/php@8.2/bin/php')->andReturn(true);
        $files->shouldNotReceive('exists')->with(BREW_PREFIX.'/opt/php@82/bin/php');
        $this->assertEquals(BREW_PREFIX.'/opt/php@8.2/bin/php', $brewMock->getPhpExecutablePath('php@8.2'));

        // Check the `/opt/homebrew/opt/php71/bin/php` location for older installations
        $brewMock = Mockery::mock(Brew::class, [
            Mockery::mock(CommandLine::class),
            $files = Mockery::mock(Filesystem::class),
        ])->makePartial();

        $files->shouldReceive('exists')->once()->with(BREW_PREFIX.'/opt/php@8.2/bin/php')->andReturn(false);
        $files->shouldReceive('exists')->with(BREW_PREFIX.'/opt/php82/bin/php')->andReturn(true);
        $this->assertEquals(BREW_PREFIX.'/opt/php82/bin/php', $brewMock->getPhpExecutablePath('php@8.2'));

        // When the default PHP is the version we are looking for
        $brewMock = Mockery::mock(Brew::class, [
            Mockery::mock(CommandLine::class),
            $files = Mockery::mock(Filesystem::class),
        ])->makePartial();

        $files->shouldReceive('exists')->once()->with(BREW_PREFIX.'/opt/php@8.2/bin/php')->andReturn(false);
        $files->shouldReceive('exists')->with(BREW_PREFIX.'/opt/php82/bin/php')->andReturn(false);
        $files->shouldReceive('isLink')->with(BREW_PREFIX.'/opt/php')->andReturn(true);
        $files->shouldReceive('readLink')->with(BREW_PREFIX.'/opt/php')->andReturn('../Cellar/php@8.2/8.2.13/bin/php');
        $this->assertEquals(BREW_PREFIX.'/opt/php/bin/php', $brewMock->getPhpExecutablePath('php@8.2'));

        // When the default PHP is not the version we are looking for
        $brewMock = Mockery::mock(Brew::class, [
            Mockery::mock(CommandLine::class),
            $files = Mockery::mock(Filesystem::class),
        ])->makePartial();

        $files->shouldReceive('exists')->once()->with(BREW_PREFIX.'/opt/php@8.2/bin/php')->andReturn(false);
        $files->shouldReceive('exists')->with(BREW_PREFIX.'/opt/php82/bin/php')->andReturn(false);
        $files->shouldReceive('isLink')->with(BREW_PREFIX.'/opt/php')->andReturn(true);
        $files->shouldReceive('readLink')->with(BREW_PREFIX.'/opt/php')->andReturn('../Cellar/php@8.1/8.1.13/bin/php');
        $this->assertEquals(BREW_PREFIX.'/bin/php', $brewMock->getPhpExecutablePath('php@8.2')); // Could not find a version, so retuned the default binary

        // When no PHP Version is provided
        $brewMock = Mockery::mock(Brew::class, [
            Mockery::mock(CommandLine::class),
            Mockery::mock(Filesystem::class),
        ])->makePartial();

        $this->assertEquals(BREW_PREFIX.'/bin/php', $brewMock->getPhpExecutablePath(null));
    }

    public function test_it_can_compare_two_php_versions()
    {
        $this->assertTrue(resolve(Brew::class)->arePhpVersionsEqual('php81', 'php@8.1'));
        $this->assertTrue(resolve(Brew::class)->arePhpVersionsEqual('php81', 'php@81'));
        $this->assertTrue(resolve(Brew::class)->arePhpVersionsEqual('php81', '81'));

        $this->assertFalse(resolve(Brew::class)->arePhpVersionsEqual('php81', 'php@80'));
        $this->assertFalse(resolve(Brew::class)->arePhpVersionsEqual('php81', '82'));
    }

    /**
     * Provider of php links and their expected split matches.
     */
    public function supportedPhpLinkPathProvider(): array
    {
        return [
            [
                '/test/path/php/8.2.0/test', // linked path
                [ // matches
                    'path/php/8.2.0/test',
                    'php',
                    '',
                    '8.2',
                    '.0',
                ],
                'php', // expected link formula
            ],
            [
                '/test/path/php@8.2/8.2.13/test',
                [
                    'path/php@8.2/8.2.13/test',
                    'php',
                    '@8.2',
                    '8.2',
                    '.13',
                ],
                'php@8.2',
            ],
            [
                '/test/path/php/8.2.9_2/test',
                [
                    'path/php/8.2.9_2/test',
                    'php',
                    '',
                    '8.2',
                    '.9_2',
                ],
                'php',
            ],
            [
                '/test/path/php82/8.2.9_2/test',
                [
                    'path/php82/8.2.9_2/test',
                    'php',
                    '82',
                    '8.2',
                    '.9_2',
                ],
                'php82',
            ],
            [
                '/test/path/php81/test',
                [
                    'path/php81/test',
                    'php',
                    '81',
                    '',
                    '',
                ],
                'php81',
            ],
        ];
    }
}
