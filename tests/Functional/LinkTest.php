<?php

use Valet\Tests\Functional\FunctionalTestCase;

/**
 * @group functional
 */
class LinkTest extends FunctionalTestCase
{
    protected function setUp()
    {
        // Create filesystem structure
        mkdir($_SERVER['HOME'] . '/linked-directory');
        file_put_contents($_SERVER['HOME'] . '/linked-directory/index.html', 'Valet linked site');
    }

    protected function tearDown()
    {
        Filesystem::remove($_SERVER['HOME'] . '/linked-directory');
    }

    public function test_valet_site_is_linked_correctly()
    {
        // Call valet link command
        $this->valetCommand('link linked', $_SERVER['HOME'] . '/linked-directory');

        $response = \Httpful\Request::get('http://linked.dev')->send();

        $this->assertEquals(200, $response->code);
        $this->assertContains('Valet linked site', $response->body);
    }
}
