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
        Filesystem::removeBrokenLinksAt(VALET_HOME_PATH . '/Sites');
    }

    public function test_valet_site_is_linked()
    {
        // Call valet link command
        $this->valetCommand('link linked', $_SERVER['HOME'] . '/linked-directory');

        $response = \Httpful\Request::get('http://linked.test')->send();

        $this->assertEquals(200, $response->code);
        $this->assertContains('Valet linked site', $response->body);
    }

    public function test_valet_site_is_unlinked()
    {
        // Link site
        $this->valetCommand('link linked', $_SERVER['HOME'] . '/linked-directory');

        // Call valet unlink command
        $this->valetCommand('unlink linked', $_SERVER['HOME'] . '/linked-directory');

        $response = \Httpful\Request::get('http://linked.test')->send();

        $this->assertEquals(404, $response->code);
        $this->assertContains('Valet - Not Found', $response->body);
    }

    public function test_valet_links()
    {
        // Link site
        $this->valetCommand('link linked', $_SERVER['HOME'] . '/linked-directory');

        $response = $this->valetCommand('links');

        $this->assertContains('linked', $response);
        $this->assertContains('http://linked.test', $response);
        $this->assertContains($_SERVER['HOME'] . '/linked-directory', $response);
        // TODO: Test SSL output
    }
}
