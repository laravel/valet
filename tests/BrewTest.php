<?php

use Valet\Brew;
use Valet\Filesystem;
use Valet\CommandLine;
use Illuminate\Container\Container;

class BrewTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['SUDO_USER'] = 'Taylor';

        Container::setInstance(new Container);
    }


    public function tearDown()
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
        $cli->shouldReceive('run')->with('brew list | grep php70')->andReturn('php70');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(Brew::class)->installed('php70'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->with('brew list | grep php70')->andReturn('php70-mcrypt
php70');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(Brew::class)->installed('php70'));
    }


    public function test_installed_returns_false_when_given_formula_is_not_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->with('brew list | grep php70')->andReturn('');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Brew::class)->installed('php70'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->with('brew list | grep php70')->andReturn('php70-mcrypt');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Brew::class)->installed('php70'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->with('brew list | grep php70')->andReturn('php70-mcrypt
php70-something-else
php7');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Brew::class)->installed('php70'));
    }


    public function test_has_installed_php_indicates_if_php_is_installed_via_brew()
    {
        $brew = Mockery::mock(Brew::class.'[installed]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installed')->with('php70')->andReturn(true);
        $brew->shouldReceive('installed')->with('php56')->andReturn(true);
        $this->assertTrue($brew->hasInstalledPhp());

        $brew = Mockery::mock(Brew::class.'[installed]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installed')->with('php70')->andReturn(true);
        $brew->shouldReceive('installed')->with('php56')->andReturn(false);
        $this->assertTrue($brew->hasInstalledPhp());

        $brew = Mockery::mock(Brew::class.'[installed]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installed')->with('php70')->andReturn(false);
        $brew->shouldReceive('installed')->with('php56')->andReturn(false);
        $this->assertFalse($brew->hasInstalledPhp());
    }


    public function test_tap_taps_the_given_homebrew_repository()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('passthru')->with('sudo -u Taylor brew tap php70');
        $cli->shouldReceive('passthru')->with('sudo -u Taylor brew tap php56');
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->tap('php70', 'php56');
    }


    public function test_restart_restarts_the_service_using_homebrew_services()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietly')->with('sudo brew services restart dnsmasq');
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->restartService('dnsmasq');
    }


    public function test_stop_stops_the_service_using_homebrew_services()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietly')->with('sudo brew services stop dnsmasq');
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->stopService('dnsmasq');
    }


    public function test_linked_php_returns_linked_php_formula_name()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isLink')->with('/usr/local/bin/php')->andReturn(true);
        $files->shouldReceive('readLink')->with('/usr/local/bin/php')->andReturn('/test/path/php70/test');
        swap(Filesystem::class, $files);
        $this->assertEquals('php70', resolve(Brew::class)->linkedPhp());

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isLink')->with('/usr/local/bin/php')->andReturn(true);
        $files->shouldReceive('readLink')->with('/usr/local/bin/php')->andReturn('/test/path/php56/test');
        swap(Filesystem::class, $files);
        $this->assertEquals('php56', resolve(Brew::class)->linkedPhp());
    }


    /**
     * @expectedException DomainException
     */
    public function test_linked_php_throws_exception_if_no_php_link()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isLink')->with('/usr/local/bin/php')->andReturn(false);
        swap(Filesystem::class, $files);
        resolve(Brew::class)->linkedPhp();
    }


    /**
     * @expectedException DomainException
     */
    public function test_linked_php_throws_exception_if_unsupported_php_version_is_linked()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isLink')->with('/usr/local/bin/php')->andReturn(true);
        $files->shouldReceive('readLink')->with('/usr/local/bin/php')->andReturn('/test/path/php42/test');
        swap(Filesystem::class, $files);
        resolve(Brew::class)->linkedPhp();
    }


    public function test_install_or_fail_will_install_brew_formulas()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->with('brew install dnsmasq', Mockery::type('Closure'));
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->installOrFail('dnsmasq');
    }


    public function test_install_or_fail_can_install_taps()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->with('brew install dnsmasq', Mockery::type('Closure'));
        swap(CommandLine::class, $cli);
        $brew = Mockery::mock(Brew::class.'[tap]', [$cli, new Filesystem]);
        $brew->shouldReceive('tap')->with(['test/tap']);
        $brew->installOrFail('dnsmasq', ['test/tap']);
    }


    /**
     * @expectedException DomainException
     */
    public function test_install_or_fail_throws_exception_on_failure()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->andReturnUsing(function ($command, $onError) {
            $onError('test error ouput');
        });
        swap(CommandLine::class, $cli);
        resolve(Brew::class)->installOrFail('dnsmasq');
    }
}
