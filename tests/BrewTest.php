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
    public function set_up()
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container);
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
        $cli->shouldReceive('runAsUser')->once()->with('brew info php@7.4 --json')
        ->andReturn('[{"name":"php@7.4","full_name":"php@7.4","aliases":[],"versioned_formulae":[],"versions":{"stable":"7.4.5"},"installed":[{"version":"7.4.5"}]}]');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(Brew::class)->installed('php@7.4'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew info php --json')
        ->andReturn('[{"name":"php","full_name":"php","aliases":["php@8.0"],"versioned_formulae":[],"versions":{"stable":"8.0.0"},"installed":[{"version":"8.0.0"}]}]');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(Brew::class)->installed('php'));
    }

    public function test_installed_returns_false_when_given_formula_is_not_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew info php@7.4 --json')->andReturn('');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Brew::class)->installed('php@7.4'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew info php@7.4 --json')->andReturn('Error: No formula found');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Brew::class)->installed('php@7.4'));
    }

    public function test_has_installed_php_indicates_if_php_is_installed_via_brew()
    {
        $brew = Mockery::mock(Brew::class.'[installedPhpFormulae]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedPhpFormulae')->andReturn(collect(['php@7.2']));
        $this->assertFalse($brew->hasInstalledPhp());

        $brew = Mockery::mock(Brew::class.'[installedPhpFormulae]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedPhpFormulae')->andReturn(collect(['php@8.1']));
        $this->assertTrue($brew->hasInstalledPhp());

        $brew = Mockery::mock(Brew::class.'[installedPhpFormulae]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedPhpFormulae')->andReturn(collect(['php@8.0']));
        $this->assertTrue($brew->hasInstalledPhp());

        $brew = Mockery::mock(Brew::class.'[installedPhpFormulae]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedPhpFormulae')->andReturn(collect(['php@7.4']));
        $this->assertTrue($brew->hasInstalledPhp());

        $brew = Mockery::mock(Brew::class.'[installedPhpFormulae]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedPhpFormulae')->andReturn(collect(['php@7.3']));
        $this->assertTrue($brew->hasInstalledPhp());

        $brew = Mockery::mock(Brew::class.'[installedPhpFormulae]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedPhpFormulae')->andReturn(collect(['php73']));
        $this->assertTrue($brew->hasInstalledPhp());
    }

    public function test_tap_taps_the_given_homebrew_repository()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('passthru')->once()->with('sudo -u "'.user().'" brew tap php@8.1');
        $cli->shouldReceive('passthru')->once()->with('sudo -u "'.user().'" brew tap php@8.0');
        $cli->shouldReceive('passthru')->once()->with('sudo -u "'.user().'" brew tap php@7.4');
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->tap('php@8.1', 'php@8.0', 'php@7.4');
    }

    public function test_restart_restarts_the_service_using_homebrew_services()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew info dnsmasq --json')->andReturn('[{"name":"dnsmasq","full_name":"dnsmasq","aliases":[],"versioned_formulae":[],"versions":{"stable":"1"},"installed":[{"version":"1"}]}]');
        $cli->shouldReceive('quietly')->once()->with('brew services stop dnsmasq');
        $cli->shouldReceive('quietly')->once()->with('sudo brew services stop dnsmasq');
        $cli->shouldReceive('quietly')->once()->with('sudo brew services start dnsmasq');
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->restartService('dnsmasq');
    }

    public function test_stop_stops_the_service_using_homebrew_services()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew info dnsmasq --json')->andReturn('[{"name":"dnsmasq","full_name":"dnsmasq","aliases":[],"versioned_formulae":[],"versions":{"stable":"1"},"installed":[{"version":"1"}]}]');
        $cli->shouldReceive('quietly')->once()->with('brew services stop dnsmasq');
        $cli->shouldReceive('quietly')->once()->with('sudo brew services stop dnsmasq');
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
        $files->shouldReceive('readLink')->once()->with(BREW_PREFIX.'/bin/php')->andReturn('/test/path/php/7.4.0/test');
        $this->assertSame('php@7.4', $getBrewMock($files)->linkedPhp());

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('readLink')->once()->with(BREW_PREFIX.'/bin/php')->andReturn('/test/path/php/7.3.0/test');
        $this->assertSame('php@7.3', $getBrewMock($files)->linkedPhp());

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('readLink')->once()->with(BREW_PREFIX.'/bin/php')->andReturn('/test/path/php@8.0/8.0.13/test');
        $this->assertSame('php@8.0', $getBrewMock($files)->linkedPhp());

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('readLink')->once()->with(BREW_PREFIX.'/bin/php')->andReturn('/test/path/php/8.0.13_2/test');
        $this->assertSame('php@8.0', $getBrewMock($files)->linkedPhp());

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('readLink')->once()->with(BREW_PREFIX.'/bin/php')->andReturn('/test/path/php80/8.0.13_2/test');
        $this->assertSame('php@8.0', $getBrewMock($files)->linkedPhp());

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('readLink')->once()->with(BREW_PREFIX.'/bin/php')->andReturn('/test/path/php80/test');
        $this->assertSame('php@8.0', $getBrewMock($files)->linkedPhp());
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
        $cli->shouldReceive('runAsUser')->once()->with('brew install dnsmasq', Mockery::type('Closure'));
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->installOrFail('dnsmasq');
    }

    public function test_install_or_fail_can_install_taps()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew install dnsmasq', Mockery::type('Closure'));
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
     *
     * @param $path
     * @param $matches
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
     *
     * @param $path
     * @param $matches
     * @param $expectedLinkFormula
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
        $brewMock->shouldReceive('getLinkedPhpFormula')->once()->andReturn('php@7.2-test');
        $brewMock->shouldReceive('restartService')->once()->with('php@7.2-test');
        $brewMock->restartLinkedPhp();
    }

    /**
     * Provider of php links and their expected split matches.
     *
     * @return array
     */
    public function supportedPhpLinkPathProvider()
    {
        return [
            [
                '/test/path/php/7.4.0/test', // linked path
                [ // matches
                    'path/php/7.4.0/test',
                    'php',
                    '',
                    '7.4',
                    '.0',
                ],
                'php', // expected link formula
            ],
            [
                '/test/path/php@7.4/7.4.13/test',
                [
                    'path/php@7.4/7.4.13/test',
                    'php',
                    '@7.4',
                    '7.4',
                    '.13',
                ],
                'php@7.4',
            ],
            [
                '/test/path/php/7.4.9_2/test',
                [
                    'path/php/7.4.9_2/test',
                    'php',
                    '',
                    '7.4',
                    '.9_2',
                ],
                'php',
            ],
            [
                '/test/path/php74/7.4.9_2/test',
                [
                    'path/php74/7.4.9_2/test',
                    'php',
                    '74',
                    '7.4',
                    '.9_2',
                ],
                'php74',
            ],
            [
                '/test/path/php56/test',
                [
                    'path/php56/test',
                    'php',
                    '56',
                    '',
                    '',
                ],
                'php56',
            ],
        ];
    }
}
