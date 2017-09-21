<?php

use Valet\Tests\Functional\FunctionalTestCase;

/**
 * @group functional
 */
class InstallTest extends FunctionalTestCase
{
    public function test_valet_is_running_after_install()
    {
        $response = \Httpful\Request::get('http://test.test')->send();

        $this->assertEquals(404, $response->code);
        $this->assertContains('Valet - Not Found', $response->body);
    }
}
