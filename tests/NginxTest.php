<?php

use Valet\Site;
use Valet\Nginx;
use Valet\Filesystem;
use Valet\Configuration;
use function Valet\user;
use function Valet\resolve;
use function Valet\swap;
use Illuminate\Container\Container;

class NginxTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container);
    }


    public function tearDown()
    {
        Mockery::close();
    }


    public function test_install_nginx_configuration_places_nginx_base_configuration_in_proper_location()
    {
        $files = Mockery::mock(Filesystem::class.'[putAsUser]');

        $files->shouldReceive('putAsUser')->andReturnUsing(function ($path, $contents) {
            $this->assertSame('/usr/local/etc/nginx/nginx.conf', $path);
            $this->assertContains('include '.VALET_HOME_PATH.'/Nginx/*', $contents);
        })->once();

        swap(Filesystem::class, $files);

        $nginx = resolve(Nginx::class);
        $nginx->installConfiguration();
    }


    public function test_install_caddy_directories_creates_location_for_site_specific_configuration()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isDir')->with(VALET_HOME_PATH.'/Nginx')->andReturn(false);
        $files->shouldReceive('mkdirAsUser')->with(VALET_HOME_PATH.'/Nginx')->once();
        $files->shouldReceive('putAsUser')->with(VALET_HOME_PATH.'/Nginx/.keep', "\n")->once();

        swap(Filesystem::class, $files);
        swap(Configuration::class, Mockery::spy(Configuration::class));
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
        swap(Configuration::class, Mockery::spy(Configuration::class));
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
        swap(Configuration::class, $config = Mockery::spy(Configuration::class, ['read' => ['tld' => 'test']]));
        swap(Site::class, $site = Mockery::spy(Site::class));

        $nginx = resolve(Nginx::class);
        $nginx->installNginxDirectory();

        $site->shouldHaveReceived('resecureForNewTld', ['test', 'test']);
    }

}
