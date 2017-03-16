<?php

use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class InstallTest extends TestCase
{
    public function test_valet_is_running_after_install()
    {
        $response = \Httpful\Request::get('http://test.dev')->send();

        $this->assertEquals(404, $response->code);
        $this->assertContains('Valet - Not Found', $response->body);
    }
}
