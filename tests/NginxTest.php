<?php

use Illuminate\Container\Container;
use Valet\Configuration;
use Valet\Filesystem;
use Valet\Nginx;
use function Valet\resolve;
use Valet\Site;
use function Valet\swap;
use function Valet\user;

class NginxTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
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

    public function test_install_nginx_configuration_places_nginx_base_configuration_in_proper_location()
    {
        $files = Mockery::mock(Filesystem::class.'[putAsUser]');

        $files->shouldReceive('putAsUser')->andReturnUsing(function ($path, $contents) {
            $this->assertSame(BREW_PREFIX.'/etc/nginx/nginx.conf', $path);
            $this->assertStringContainsString('include "'.VALET_HOME_PATH.'/Nginx/*"', $contents);
        })->once();

        swap(Filesystem::class, $files);

        $nginx = resolve(Nginx::class);
        $nginx->installConfiguration();
    }

    public function test_install_nginx_directories_creates_location_for_site_specific_configuration()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isDir')->with(VALET_HOME_PATH.'/Nginx')->andReturn(false);
        $files->shouldReceive('mkdirAsUser')->with(VALET_HOME_PATH.'/Nginx')->once();
        $files->shouldReceive('putAsUser')->with(VALET_HOME_PATH.'/Nginx/.keep', "\n")->once();

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config = Mockery::spy(Configuration::class, ['read' => ['tld' => 'test', 'loopback' => VALET_LOOPBACK]]));
        swap(Site::class, Mockery::spy(Site::class));

        $nginx = resolve(Nginx::class);
        $nginx->installNginxDirectory();
    }

    public function test_nginx_directory_is_never_created_if_it_already_exists()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isDir')->with(VALET_HOME_PATH.'/Nginx')->andReturn(true);
        $files->shouldReceive('mkdirAsUser')->never();
        $files->shouldReceive('putAsUser')->with(VALET_HOME_PATH.'/Nginx/.keep', "\n")->once();

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config = Mockery::spy(Configuration::class, ['read' => ['tld' => 'test', 'loopback' => VALET_LOOPBACK]]));
        swap(Site::class, Mockery::spy(Site::class));

        $nginx = resolve(Nginx::class);
        $nginx->installNginxDirectory();
    }

    public function test_install_nginx_directories_rewrites_secure_nginx_files()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isDir')->with(VALET_HOME_PATH.'/Nginx')->andReturn(false);
        $files->shouldReceive('mkdirAsUser')->with(VALET_HOME_PATH.'/Nginx')->once();
        $files->shouldReceive('putAsUser')->with(VALET_HOME_PATH.'/Nginx/.keep', "\n")->once();

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config = Mockery::spy(Configuration::class, ['read' => ['tld' => 'test', 'loopback' => VALET_LOOPBACK]]));
        swap(Site::class, $site = Mockery::spy(Site::class));

        $nginx = resolve(Nginx::class);
        $nginx->installNginxDirectory();

        $data = ['tld' => 'test', 'loopback' => '127.0.0.1'];

        $site->shouldHaveReceived('resecureForNewConfiguration', [$data, $data]);
    }

    public function test_it_gets_configured_sites()
    {
        $files = Mockery::mock(Filesystem::class);

        $files->shouldReceive('scandir')
            ->once()
            ->with(VALET_HOME_PATH.'/Nginx')
            ->andReturn(['.gitkeep', 'isolated-site-71.test', 'isolated-site-72.test', 'isolated-site-73.test']);

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config = Mockery::spy(Configuration::class, ['read' => ['tld' => 'test', 'loopback' => VALET_LOOPBACK]]));
        swap(Site::class, Mockery::mock(Site::class));

        $nginx = resolve(Nginx::class);
        $output = $nginx->configuredSites();

        $this->assertEquals(
            ['isolated-site-71.test', 'isolated-site-72.test', 'isolated-site-73.test'],
            $output->values()->all()
        );
    }
}
