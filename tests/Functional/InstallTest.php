<?php

use Valet\Tests\Functional\FunctionalTestCase;

/**
 * @group functional
 * @group acceptance
 */
class InstallTest extends FunctionalTestCase
{
    public function test_valet_is_running_after_install()
    {
        $response = \Httpful\Request::get('http://test.test')->send();

        $this->assertEquals(404, $response->code);
        $this->assertContains('Valet - Not Found', $response->body);
    }

    public function test_dns_record_is_correct()
    {
        $record = dns_get_record('test.test', DNS_A)[0];

        $this->assertEquals('127.0.0.1', $record['ip']);
    }
}
