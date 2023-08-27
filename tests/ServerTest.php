<?php

use Illuminate\Container\Container;
use Valet\Server;

use function Valet\user;

class ServerTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
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

    public function test_it_extracts_uri_from_server_request_uri()
    {
        $this->assertEquals('/about/index.php', Server::uriFromRequestUri('/about/index.php?abc=def&qrs=tuv'));
        $this->assertEquals('/', Server::uriFromRequestUri('/?abc=def&qrs=tuv'));
    }

    public function test_it_extracts_domain_from_site_name()
    {
        $this->assertEquals('tighten', Server::domainFromSiteName('subdomain.tighten'));
    }

    public function test_it_gets_site_name_from_http_host()
    {
        $server = new Server(['tld' => 'test']);

        $httpHost = 'tighten.test';
        $this->assertEquals('tighten', $server->siteNameFromHttpHost($httpHost));
    }

    public function test_it_gets_site_name_from_http_host_using_wildcard()
    {
        $server = new Server(['tld' => 'test']);

        $httpHost = 'tighten.192.168.0.10.nip.io';
        $this->assertEquals('tighten', $server->siteNameFromHttpHost($httpHost));
        $httpHost = 'tighten-192-168-0-10.nip.io';
        $this->assertEquals('tighten', $server->siteNameFromHttpHost($httpHost));
    }

    public function test_it_strips_www_dot_from_http_host()
    {
        $server = new Server(['tld' => 'test']);

        $httpHost = 'www.tighten.test';
        $this->assertEquals('tighten', $server->siteNameFromHttpHost($httpHost));
    }

    public function test_it_gets_site_path_from_site_name()
    {
        $server = new Server(['paths' => [__DIR__.'/files/sites']]);

        $realPath = __DIR__.'/files/sites/tighten';
        $this->assertEquals($realPath, $server->sitePath('tighten'));
        $realPath = __DIR__.'/files/sites/tighten';
        $this->assertEquals($realPath, $server->sitePath('subdomain.tighten'));
    }

    public function test_it_returns_null_if_site_does_not_match()
    {
        $server = new Server(['paths' => []]);

        $this->assertNull($server->sitePath('tighten'));
    }

    public function test_it_gets_default_site_path()
    {
        $server = new Server(['default' => __DIR__.'/files/sites/tighten']);

        $this->assertEquals(__DIR__.'/files/sites/tighten', $server->defaultSitePath());
    }

    public function test_it_returns_null_default_site_path_if_not_set()
    {
        $server = new Server([]);

        $this->assertNull($server->defaultSitePath());
    }

    public function test_it_tests_whether_host_is_ip_address()
    {
        $this->assertTrue(Server::hostIsIpAddress('192.168.1.1'));
        $this->assertFalse(Server::hostIsIpAddress('google.com'));
        $this->assertFalse(Server::hostIsIpAddress('19.google.com'));
        $this->assertFalse(Server::hostIsIpAddress('19.19.19.19.google.com'));
    }

    public function test_it_extracts_host_from_ip_address_uri()
    {
        $this->assertEquals('onramp.test', Server::valetSiteFromIpAddressUri('onramp.test/auth/login', 'test'));
        $this->assertNull(Server::valetSiteFromIpAddressUri('onramp.dev/auth/login', 'test'));
    }
}
