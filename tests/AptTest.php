<?php

use Valet\Os\Linux\Apt;
use function Valet\resolve;
use function Valet\user;

class AptTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
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

    public function test_apt_can_be_resolved_from_container()
    {
        $this->assertInstanceOf(Apt::class, resolve(Apt::class));
    }
}
