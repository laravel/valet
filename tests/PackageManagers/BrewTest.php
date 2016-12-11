<?php

use Valet\Contracts\PackageManager;
use Valet\Filesystem;
use Valet\CommandLine;
use Illuminate\Container\Container;
use Valet\PackageManagers\Brew;

class BrewTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container);

        Container::getInstance()->bind(PackageManager::class, Brew::class);
    }


    public function tearDown()
    {
        Mockery::close();
    }


    public function test_brew_can_be_resolved_from_container()
    {
        $this->assertInstanceOf(Brew::class, resolve(PackageManager::class));
    }


    public function test_installed_returns_true_when_given_formula_is_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew list | grep php71')->andReturn('php71');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(PackageManager::class)->installed('php'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew list | grep php71')->andReturn('');
        $cli->shouldReceive('runAsUser')->once()->with('brew list | grep php70')->andReturn('php70-mcrypt
php70');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(PackageManager::class)->installed('php'));
    }


    public function test_installed_returns_false_when_given_formula_is_not_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew list | grep php70')->andReturn('');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(PackageManager::class)->installed('php70'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew list | grep php70')->andReturn('php70-mcrypt');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(PackageManager::class)->installed('php70'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew list | grep php70')->andReturn('php70-mcrypt
php70-something-else
php7');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(PackageManager::class)->installed('php70'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew list | grep php71')->andReturn('');
        $cli->shouldReceive('runAsUser')->once()->with('brew list | grep php70')->andReturn('');
        $cli->shouldReceive('runAsUser')->once()->with('brew list | grep php56')->andReturn('');
        $cli->shouldReceive('runAsUser')->once()->with('brew list | grep php55')->andReturn('');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(PackageManager::class)->installed('php'));
    }


    public function test_has_installed_php_indicates_if_php_is_installed_via_brew()
    {
        $brew = Mockery::mock(Brew::class.'[installedCheck]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedCheck')->once()->with('php71')->andReturn(true);
        $brew->shouldReceive('installedCheck')->with('php70')->andReturn(true);
        $brew->shouldReceive('installedCheck')->with('php56')->andReturn(true);
        $brew->shouldReceive('installedCheck')->with('php55')->andReturn(true);
        $this->assertTrue($brew->installed('php'));

        $brew = Mockery::mock(Brew::class.'[installedCheck]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedCheck')->once()->with('php70')->andReturn(true);
        $brew->shouldReceive('installedCheck')->with('php71')->andReturn(false);
        $brew->shouldReceive('installedCheck')->with('php56')->andReturn(false);
        $brew->shouldReceive('installedCheck')->with('php55')->andReturn(false);
        $this->assertTrue($brew->installed('php'));

        $brew = Mockery::mock(Brew::class.'[installedCheck]', [new CommandLine, new Filesystem]);
        $brew->shouldReceive('installedCheck')->once()->with('php71')->andReturn(false);
        $brew->shouldReceive('installedCheck')->once()->with('php70')->andReturn(false);
        $brew->shouldReceive('installedCheck')->once()->with('php56')->andReturn(false);
        $brew->shouldReceive('installedCheck')->once()->with('php55')->andReturn(false);
        $this->assertFalse($brew->installed('php'));
    }


    public function test_tap_taps_the_given_homebrew_repository()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('passthru')->once()->with('sudo -u '.user().' brew tap php70');
        $cli->shouldReceive('passthru')->once()->with('sudo -u '.user().' brew tap php56');
        swap(CommandLine::class, $cli);
        resolve(PackageManager::class)->tap('php70', 'php56');
    }


    public function test_install_or_fail_will_install_brew_formulas()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew install dnsmasq', Mockery::type('Closure'));
        swap(CommandLine::class, $cli);
        resolve(PackageManager::class)->installOrFail('dnsmasq');
    }


    public function test_install_or_fail_can_install_taps()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->with('brew install php', Mockery::type('Closure'));
        swap(CommandLine::class, $cli);
        $brew = Mockery::mock(Brew::class.'[tap]', [$cli, new Filesystem]);
        $brew->shouldReceive('tap')->once()->with(['homebrew/dupes', 'homebrew/versions', 'homebrew/homebrew-php']);
        $brew->installOrFail('php');
    }


    /**
     * @expectedException DomainException
     */
    public function test_install_or_fail_throws_exception_on_failure()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->andReturnUsing(function ($command, $onError) {
            $onError(1, 'test error ouput');
        });
        swap(CommandLine::class, $cli);
        resolve(PackageManager::class)->installOrFail('dnsmasq');
    }
}
