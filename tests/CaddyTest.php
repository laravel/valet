<?php

use Valet\Site;
use Valet\Caddy;
use Valet\Filesystem;
use Valet\Configuration;
use Illuminate\Container\Container;

class CaddyTest extends PHPUnit_Framework_TestCase
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


    public function test_install_caddy_file_places_stub_in_valet_home_directory()
    {
        $files = Mockery::mock(Filesystem::class.'[putAsUser]');

        $files->shouldReceive('putAsUser')->andReturnUsing(function ($path, $contents) {
            $this->assertSame(VALET_HOME_PATH.'/Caddyfile', $path);
            $this->assertTrue(strpos($contents, 'import '.VALET_HOME_PATH.'/Caddy/*') !== false);
        })->once();

        swap(Filesystem::class, $files);

        $caddy = resolve(Caddy::class);
        $caddy->installCaddyFile();
    }


    public function test_install_caddy_directories_creates_location_for_site_specific_configuration()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isDir')->with(VALET_HOME_PATH.'/Caddy')->andReturn(false);
        $files->shouldReceive('mkdirAsUser')->with(VALET_HOME_PATH.'/Caddy')->once();
        $files->shouldReceive('putAsUser')->with(VALET_HOME_PATH.'/Caddy/.keep', "\n")->once();

        swap(Filesystem::class, $files);
        swap(Configuration::class, Mockery::spy(Configuration::class));
        swap(Site::class, Mockery::spy(Site::class));

        $caddy = resolve(Caddy::class);
        $caddy->installCaddyDirectory();
    }


    public function test_caddy_directory_is_never_created_if_it_already_exists()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isDir')->with(VALET_HOME_PATH.'/Caddy')->andReturn(true);
        $files->shouldReceive('mkdirAsUser')->never();
        $files->shouldReceive('putAsUser')->with(VALET_HOME_PATH.'/Caddy/.keep', "\n")->once();

        swap(Filesystem::class, $files);
        swap(Configuration::class, Mockery::spy(Configuration::class));
        swap(Site::class, Mockery::spy(Site::class));

        $caddy = resolve(Caddy::class);
        $caddy->installCaddyDirectory();
    }


    public function test_install_caddy_directories_rewrites_secure_caddy_files()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isDir')->with(VALET_HOME_PATH.'/Caddy')->andReturn(false);
        $files->shouldReceive('mkdirAsUser')->with(VALET_HOME_PATH.'/Caddy')->once();
        $files->shouldReceive('putAsUser')->with(VALET_HOME_PATH.'/Caddy/.keep', "\n")->once();

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config = Mockery::spy(Configuration::class, ['read' => ['domain' => 'dev']]));
        swap(Site::class, $site = Mockery::spy(Site::class));

        $caddy = resolve(Caddy::class);
        $caddy->installCaddyDirectory();

        $site->shouldHaveReceived('resecureForNewDomain', ['dev', 'dev']);
    }


    public function test_caddy_daemon_is_placed_in_correct_location()
    {
        $files = Mockery::mock(Filesystem::class.'[put]');

        swap(Filesystem::class, $files);
        swap(Configuration::class, Mockery::spy(Configuration::class));
        swap(Site::class, Mockery::spy(Site::class));

        $caddy = resolve(Caddy::class);

        $files->shouldReceive('put')->andReturnUsing(function ($path, $contents) use ($caddy) {
            $this->assertSame($caddy->daemonPath, $path);
            $this->assertTrue(strpos($contents, VALET_HOME_PATH) !== false);
        })->once();

        $caddy->installCaddyDaemon();
    }
}
