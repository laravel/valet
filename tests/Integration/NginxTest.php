<?php

use Valet\Site;
use Valet\Nginx;
use Valet\Filesystem;
use Valet\Configuration;
use Valet\CommandLine;
use Valet\Contracts\PackageManager;
use Valet\Contracts\ServiceManager;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class NginxTest extends TestCase
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


    public function test_install_calls_the_right_methods()
    {
        $site = Mockery::mock(Site::class);
        $conf = Mockery::mock(Configuration::class);
        $cli = Mockery::mock(CommandLine::class);

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('ensureDirExists')->with('/etc/nginx/sites-available')->once();
        $files->shouldReceive('ensureDirExists')->with('/etc/nginx/sites-enabled')->once();

        $pm = Mockery::mock(PackageManager::class);
        $pm->shouldReceive('ensureInstalled')->with('nginx')->once();

        $sm = Mockery::mock(ServiceManager::class);
        $sm->shouldReceive('enable')->with('nginx')->once();
        $sm->shouldReceive('stop')->with('nginx')->once();

        $nginx = Mockery::mock(Nginx::class.'[installConfiguration,installServer,installNginxDirectory]', [$pm, $sm, $cli, $files, $conf, $site]);

        $nginx->shouldReceive('installConfiguration')->once();
        $nginx->shouldReceive('installServer')->once();
        $nginx->shouldReceive('installNginxDirectory')->once();

        $nginx->install();
    }


    public function test_install_nginx_configuration_places_nginx_base_configuration_in_proper_location()
    {
        $files = Mockery::mock(Filesystem::class.'[putAsUser,backup]');

        $files->shouldReceive('putAsUser')->andReturnUsing(function ($path, $contents) {
            $this->assertSame('/etc/nginx/nginx.conf', $path);
            $this->assertTrue(strpos($contents, "user '".user()."' '".group()."'") !== false);
            $this->assertTrue(strpos($contents, 'include '.VALET_HOME_PATH.'/Nginx/*') !== false);
        })->once();

        $files->shouldReceive('backup')->with('/etc/nginx/nginx.conf')->once();

        swap(Filesystem::class, $files);
        swap(PackageManager::class, Mockery::mock(PackageManager::class));
        swap(ServiceManager::class, Mockery::mock(ServiceManager::class));

        $nginx = resolve(Nginx::class);
        $nginx->installConfiguration();
    }


    public function test_install_nginx_server_places_nginx_base_configuration_in_proper_location()
    {

        $files = Mockery::mock(Filesystem::class.'[putAsUser,exists,backup,unlink]');
        $cli = Mockery::mock(CommandLine::class.'[run]');

        $files->shouldReceive('putAsUser')->andReturnUsing(function ($path, $contents) {
            $this->assertSame('/etc/nginx/sites-available/valet.conf', $path);
            $this->assertTrue(strpos($contents, 'rewrite ^ '.VALET_SERVER_PATH.' last') !== false);
            $this->assertTrue(strpos($contents, 'error_page 404 '.VALET_SERVER_PATH) !== false);
            $this->assertTrue(strpos($contents, 'fastcgi_index '.VALET_SERVER_PATH) !== false);
            $this->assertTrue(strpos($contents, 'fastcgi_param SCRIPT_FILENAME '.VALET_SERVER_PATH) !== false);
            $this->assertTrue(strpos($contents, 'error_log '.VALET_HOME_PATH.'/Log/nginx-error.log') !== false);
            $this->assertTrue(strpos($contents, 'fastcgi_pass unix:'.VALET_HOME_PATH.'/valet.sock') !== false);
        })->once();

        $files->shouldReceive('exists')->with('/etc/nginx/sites-enabled/default')->andReturn(true)->once();
        $files->shouldReceive('unlink')->with('/etc/nginx/sites-enabled/default')->once();
        $files->shouldReceive('backup')->with('/etc/nginx/fastcgi_params')->once();

        $files->shouldReceive('putAsUser')->andReturnUsing(function ($path, $contents) {
            $this->assertSame('/etc/nginx/fastcgi_params', $path);
        })->once();

        $cli->shouldReceive('run')->with('ln -snf /etc/nginx/sites-available/valet.conf /etc/nginx/sites-enabled/valet.conf')->once();

        swap(Filesystem::class, $files);
        swap(CommandLine::class, $cli);
        swap(Configuration::class, $config = Mockery::spy(Configuration::class, ['read' => ['port' => '80']]));
        swap(PackageManager::class, Mockery::mock(PackageManager::class));
        swap(ServiceManager::class, Mockery::mock(ServiceManager::class));

        $nginx = resolve(Nginx::class);
        $nginx->installServer();
    }


    public function test_install_nginx_directories_creates_location_for_site_specific_configuration()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isDir')->with(VALET_HOME_PATH.'/Nginx')->andReturn(false);
        $files->shouldReceive('mkdirAsUser')->with(VALET_HOME_PATH.'/Nginx')->once();
        $files->shouldReceive('putAsUser')->with(VALET_HOME_PATH.'/Nginx/.keep', "\n")->once();

        swap(Filesystem::class, $files);
        swap(Configuration::class, Mockery::spy(Configuration::class));
        swap(Site::class, Mockery::spy(Site::class));
        swap(PackageManager::class, Mockery::mock(PackageManager::class));
        swap(ServiceManager::class, Mockery::mock(ServiceManager::class));

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
        swap(PackageManager::class, Mockery::mock(PackageManager::class));
        swap(ServiceManager::class, Mockery::mock(ServiceManager::class));

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
        swap(Configuration::class, $config = Mockery::spy(Configuration::class, ['read' => ['domain' => 'test']]));
        swap(Site::class, $site = Mockery::spy(Site::class));
        swap(PackageManager::class, Mockery::mock(PackageManager::class));
        swap(ServiceManager::class, Mockery::mock(ServiceManager::class));

        $nginx = resolve(Nginx::class);
        $nginx->installNginxDirectory();

        $site->shouldHaveReceived('resecureForNewDomain', ['test', 'test']);
    }
}
