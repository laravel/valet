<?php

use Illuminate\Container\Container;
use Silly\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Valet\Commands\Tld;
use function Valet\user;

class ValetCommandTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
{
    public function set_up()
    {
        $_SERVER['SUDO_USER'] = user();
        $this->container = new Container;
        $this->app = new Application('Laravel Valet', '1.1.1');

        $this->app->add(Container::getInstance()[Tld::class]);
    }

    public function tear_down()
    {
        Mockery::close();
    }

    public function test_tld_command()
    {
        // $kernel = self::bootKernel();
        // $application = new Application($kernel);
        // $application = new Application('Laravel Valet', '1.1.1');
        $command = $this->app->find('tld');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            // pass arguments to the helper
            // 'username' => 'Wouter',

            // prefix the key with two dashes when passing options,
            // e.g: '--some-option' => 'option_value',
        ]);

        $commandTester->assertCommandIsSuccessful();

        // The output of the command in the console
        $output = $commandTester->getDisplay();
        // dd($output);
        // $this->assertStringContainsString('test', $output);

        // ...
    }
}
