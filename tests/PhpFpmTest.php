<?php

use Valet\PhpFpm;
use Illuminate\Container\Container;

class PhpFpmTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container);
    }


    public function tearDown()
    {
        exec('rm -rf '.__DIR__.'/output');
        mkdir(__DIR__.'/output');
        touch(__DIR__.'/output/.gitkeep');

        Mockery::close();
    }


    public function test_install_configuration_replaces_user_and_sock_in_config_file()
    {
        copy(__DIR__.'/files/fpm.conf', __DIR__.'/output/fpm.conf');
        resolve(StubForUpdatingFpmConfigFiles::class)->installConfiguration();
        $contents = file_get_contents(__DIR__.'/output/fpm.conf');
        $this->assertContains(sprintf("\nuser = %s", user()), $contents);
        $this->assertContains(sprintf("\nlisten.owner = %s", user()), $contents);
        $this->assertContains("\nlisten = ".VALET_HOME_PATH."/valet.sock", $contents);
    }
}


class StubForUpdatingFpmConfigFiles extends PhpFpm
{
    function fpmConfigPath()
    {
        return __DIR__.'/output/fpm.conf';
    }
}
