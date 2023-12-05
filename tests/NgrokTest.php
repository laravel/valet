<?php

use Illuminate\Container\Container;
use Valet\Ngrok;

use function Valet\resolve;
use function Valet\user;

class NgrokTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
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

    public function test_it_matches_correct_share_tunnel()
    {
        $tunnels = [
            (object) [
                'proto' => 'https',
                'config' => (object) [
                    'addr' => 'http://mysite.test:80',
                ],
                'public_url' => 'https://right-one.ngrok.io/',
            ],
            (object) [
                'proto' => 'http',
                'config' => (object) [
                    'addr' => 'http://mynewsite.test:80',
                ],
                'public_url' => 'http://new-right-one.ngrok.io/',
            ],
        ];

        $ngrok = resolve(Ngrok::class);
        $this->assertEquals('https://right-one.ngrok.io/', $ngrok->findHttpTunnelUrl($tunnels, 'mysite'));
        $this->assertEquals('http://new-right-one.ngrok.io/', $ngrok->findHttpTunnelUrl($tunnels, 'mynewsite'));

    }

    public function test_it_checks_against_lowercased_domain()
    {
        $tunnels = [
            (object) [
                'proto' => 'http',
                'config' => (object) [
                    'addr' => 'http://mysite.test:80',
                ],
                'public_url' => 'http://right-one.ngrok.io/',
            ],
        ];

        $ngrok = resolve(Ngrok::class);
        $this->assertEquals('http://right-one.ngrok.io/', $ngrok->findHttpTunnelUrl($tunnels, 'MySite'));
    }
}
