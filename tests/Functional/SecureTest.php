<?php

namespace Valet\Tests\Functional;

use Filesystem;
use Httpful\Exception\ConnectionErrorException;

/**
 * @group functional
 */
class SecureTest extends FunctionalTestCase
{
    protected function setUp()
    {
        // Create filesystem structure
        mkdir($_SERVER['HOME'] . '/valet-site');
        file_put_contents($_SERVER['HOME'] . '/valet-site/index.html', 'Valet site');
        $this->valetCommand('link', $_SERVER['HOME'] . '/valet-site');
    }

    protected function tearDown()
    {
        $this->valetCommand('unlink', $_SERVER['HOME'] . '/valet-site');
        Filesystem::remove($_SERVER['HOME'] . '/valet-site');
    }

    public function test_valet_can_enable_https()
    {
        $this->valetCommand('secure', $_SERVER['HOME'] . '/valet-site');

        $response = \Httpful\Request::get('https://valet-site.test')->send();

        $this->assertEquals(200, $response->code);
        $this->assertContains('Valet site', $response->body);
    }

    public function test_valet_can_disable_https()
    {
        $this->expectException(ConnectionErrorException::class);

        $this->valetCommand('unsecure', $_SERVER['HOME'] . '/valet-site');

        \Httpful\Request::get('https://valet-site.test')->send();
    }
}