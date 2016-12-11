<?php

use Illuminate\Container\Container;
use Valet\CommandLine;
use Valet\Contracts\PackageManager;
use Valet\PackageManagers\Dnf;

class DnfTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container);

        Container::getInstance()->bind(PackageManager::class, Dnf::class);
    }


    public function tearDown()
    {
        Mockery::close();
    }


    public function test_apt_can_be_resolved_from_container()
    {
        $this->assertInstanceOf(Dnf::class, resolve(PackageManager::class));
    }


    public function test_installed_returns_true_when_given_formula_is_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('dnf list installed php', Mockery::type('Closure'))
            ->andReturn('php.x86_64 5.6.28-1.fc23 @updates');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(PackageManager::class)->installed('php'));
    }


    public function test_installed_returns_false_when_given_formula_is_not_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('dnf list installed php', Mockery::type('Closure'))
            ->andReturnUsing(function ($command, $onError) {
                $onError(1, 'Package not found');
            });
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(PackageManager::class)->installed('php'));
    }


    public function test_install_or_fail_will_install_apt_formulas()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('dnf install -y dnsmasq', Mockery::type('Closure'));
        swap(CommandLine::class, $cli);
        resolve(PackageManager::class)->installOrFail('dnsmasq');
    }


    public function test_etc_dir_is_correct()
    {
        $this->assertEquals('/etc', resolve(PackageManager::class)->etcDir());
        $this->assertEquals('/etc/nginx/nginx.conf', resolve(PackageManager::class)->etcDir('nginx/nginx.conf'));
    }


    public function test_log_dir_is_correct()
    {
        $this->assertEquals('/var/log', resolve(PackageManager::class)->logDir());
        $this->assertEquals('/var/log/nginx/error.log', resolve(PackageManager::class)->logDir('nginx/error.log'));
    }


    public function test_opt_dir_is_correct()
    {
        $this->assertEquals('/opt', resolve(PackageManager::class)->optDir());
        $this->assertEquals('/opt/something.conf', resolve(PackageManager::class)->optDir('something.conf'));
    }


    /**
     * @expectedException DomainException
     */
    public function test_install_or_fail_throws_exception_on_failure()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->andReturnUsing(function ($command, $onError) {
            $onError(1, 'test error ouput');
        });
        swap(CommandLine::class, $cli);
        resolve(PackageManager::class)->installOrFail('dnsmasq');
    }
}
