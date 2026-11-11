<?php

use Illuminate\Container\Container;
use Symfony\Component\Process\Process;
use Valet\Filesystem;
use Valet\Server;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use function Valet\user;

class ServerTest extends TestCase
{
    use UsesNullWriter;

    private ?string $sandbox = null;

    public function set_up()
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container);
        $this->setNullWriter();
    }

    public function tear_down()
    {
        Mockery::close();

        if ($this->sandbox) {
            (new Filesystem)->rmDirAndContents($this->sandbox);
        }
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

    public function test_it_ignores_invalid_paths()
    {
        $server = new Server(['paths' => ['fake' => __DIR__.'/invalid_path']]);

        $this->assertNull($server->sitePath('tighten'));
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

    public function test_ip_address_requests_only_strip_the_leading_site_name()
    {
        $this->assertStringContainsString('SITE INDEX', $this->serve('127.0.0.1', '/site.test/'));

        $response = $this->serve('127.0.0.1', '/site.test/.site.test./.site.test./.site.test./outside/file.txt/');

        $this->assertStringContainsString('SITE INDEX', $response);
        $this->assertStringNotContainsString('OUTSIDE THE SITE', $response);
    }

    public function test_requests_for_parent_directories_return_404()
    {
        $response = $this->serve('site.test', '/%2e%2e/%2e%2e/%2e%2e/outside/file.txt/');

        $this->assertStringContainsString('404 - Not Found', $response);
        $this->assertStringNotContainsString('OUTSIDE THE SITE', $response);
    }

    private function serve(string $host, string $requestUri): string
    {
        $this->sandbox ??= $this->createSandbox();

        file_put_contents($this->sandbox.'/prepend.php', '<?php '.implode(' ', [
            'define("VALET_HOME_PATH", '.var_export($this->sandbox.'/home', true).');',
            '$_SERVER["HTTP_HOST"] = '.var_export($host, true).';',
            '$_SERVER["REQUEST_URI"] = '.var_export($requestUri, true).';',
        ]));

        return (new Process(
            [PHP_BINARY, '-d', 'auto_prepend_file='.$this->sandbox.'/prepend.php', 'server.php'],
            __DIR__.'/..'
        ))->mustRun()->getOutput();
    }

    private function createSandbox(): string
    {
        $sandbox = sys_get_temp_dir().'/valet-server-test-'.bin2hex(random_bytes(4));

        mkdir($sandbox.'/home/Sites/site', 0777, true);
        mkdir($sandbox.'/outside');

        file_put_contents($sandbox.'/home/config.json', json_encode(['tld' => 'test', 'paths' => [$sandbox.'/home/Sites']]));
        file_put_contents($sandbox.'/home/Sites/site/index.php', '<?php echo "SITE INDEX";');
        file_put_contents($sandbox.'/outside/file.txt', 'OUTSIDE THE SITE');

        return $sandbox;
    }
}
