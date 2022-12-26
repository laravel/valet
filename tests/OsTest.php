<?php

use Valet\Os\Linux;
use Valet\Os\Linux\Apt;
use Valet\Os\Mac;
use Valet\Os\Mac\Brew;

use function Valet\resolve;
use function Valet\user;

class OsTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
{
    use PrepsContainer;
    use UsesNullWriter;

    public function set_up()
    {
        $_SERVER['SUDO_USER'] = user();

        $this->prepContainer();
        $this->setNullWriter();
    }

    public function tear_down()
    {
        Mockery::close();
    }

    public function test_mac_returns_brew()
    {
        $mac = new Mac();
        $this->assertInstanceOf(Brew::class, $mac->installer());
    }

    public function test_linux_returns_apt()
    {
        $linux = new Linux();
        $this->assertInstanceOf(Apt::class, $linux->installer());
    }
}
