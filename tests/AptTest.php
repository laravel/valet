<?php

use Valet\CommandLine;
use Valet\Os\Linux\Apt;
use function Valet\resolve;
use function Valet\swap;
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

    public function test_it_checks_if_a_package_is_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runAsUser')->once()->withArgs([
            'dpkg -s nginx &> /dev/null',
            Mockery::type('callable'),
        ])->andReturnUsing(function ($command, $onError) {
            $onError(1, 'test error output');

            return 'this is not actually returning here because error';
        });

        swap(CommandLine::class, $cli);

        $output = resolve(Apt::class)->installed('nginx');
        $this->assertFalse($output);
    }

    public function test_it_checks_if_php_is_installed()
    {
        // @todo can you have more than one version of php installed via apt?
        // can you have a "linked" version?
        // @todo
    }
}
