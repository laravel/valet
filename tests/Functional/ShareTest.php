<?php

use Valet\Tests\Functional\FunctionalTestCase;

/**
 * @group functional
 * @group acceptance
 */
class ShareTest extends FunctionalTestCase
{
    protected function setUp()
    {
        // Create filesystem structure
        mkdir($_SERVER['HOME'] . '/valet-site');
        file_put_contents($_SERVER['HOME'] . '/valet-site/index.html', 'Valet site');
        $this->valetCommand('link valet', $_SERVER['HOME'] . '/valet-site');
    }

    protected function tearDown()
    {
        $this->valetCommand('unsecure', $_SERVER['HOME'] . '/valet-site');
        Configuration::prune();
        Filesystem::remove($_SERVER['HOME'] . '/valet-site');
        Filesystem::removeBrokenLinksAt(VALET_HOME_PATH . '/Sites');
    }

    public function test_we_can_share_an_http_site()
    {
        // Start ngrok tunnel
        $ngrok = $this->background($this->valet().' share', $_SERVER['HOME'] . '/valet-site');

        // Assert tunnel URL is reachable
        $tunnel = Ngrok::currentTunnelUrl();
        $this->assertContains('ngrok.io', $tunnel);

        $response = \Httpful\Request::get($tunnel)->send();
        $this->assertEquals(200, $response->code);
        $this->assertContains('Valet site', $response->body);

        $ngrok->stop();
    }
}
