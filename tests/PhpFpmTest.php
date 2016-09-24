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

    public function test_fpm_is_configured_with_the_correct_user_group_and_socket()
    {
        copy(__DIR__.'/files/fpm.conf', __DIR__.'/output/fpm.conf');
        resolve(StubForUpdatingFpmConfigFiles::class)->updateConfiguration();
        $contents = file_get_contents(__DIR__.'/output/fpm.conf');
        $this->assertTrue(strpos($contents, sprintf("\nuser = %s", user())) !== false);
        $this->assertTrue(strpos($contents, "\ngroup = staff") !== false);
        $this->assertTrue(strpos($contents, "\nlisten = /var/run/fpm-valet.socket") !== false);
    }
}


class StubForUpdatingFpmConfigFiles extends PhpFpm
{
    function fpmConfigPath()
    {
        return __DIR__.'/output/fpm.conf';
    }

    function fpmIniPath()
    {
        return __DIR__.'/output/ini.conf';
    }
}
