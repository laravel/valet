<?php

namespace Valet\Tests\Functional;

use Filesystem;

/**
 * @group functional
 */
class ParkTest extends FunctionalTestCase
{
    protected function setUp()
    {
        // Create filesystem structure
        mkdir($_SERVER['HOME'] . '/Code');

        mkdir($_SERVER['HOME'] . '/Code/one');
        file_put_contents($_SERVER['HOME'] . '/Code/one/index.html', 'One');

        mkdir($_SERVER['HOME'] . '/Code/two');
        file_put_contents($_SERVER['HOME'] . '/Code/two/index.html', 'Two');

        mkdir($_SERVER['HOME'] . '/Code/with spaces');
        file_put_contents($_SERVER['HOME'] . '/Code/with spaces/index.html', 'With Spaces');
    }

    protected function tearDown()
    {
        Filesystem::remove($_SERVER['HOME'] . '/Code');
    }

    public function test_valet_can_be_parked()
    {
        $this->valetCommand('park', $_SERVER['HOME'] . '/Code');

        $one = \Httpful\Request::get('http://one.dev')->send();
        $this->assertEquals(200, $one->code);
        $this->assertContains('One', $one->body);

        $two = \Httpful\Request::get('http://two.dev')->send();
        $this->assertEquals(200, $two->code);
        $this->assertContains('Two', $two->body);

        $spaces = \Httpful\Request::get('http://with-spaces.dev')->send();
        $this->assertEquals(200, $spaces->code);
        $this->assertContains('With Spaces', $spaces->body);
    }
}