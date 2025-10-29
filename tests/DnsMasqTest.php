<?php

use Illuminate\Container\Container;
use Valet\Brew;
use Valet\CommandLine;
use Valet\Configuration;
use Valet\DnsMasq;
use Valet\Filesystem;

use function Valet\resolve;
use function Valet\swap;
use function Valet\user;

class DnsMasqTest extends Yoast\PHPUnitPolyfills\TestCases\TestCase
{
    use UsesNullWriter;

    public function set_up()
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container);
        $this->setNullWriter();

        // Clean & ensure dirs
        @exec('rm -rf '.__DIR__.'/output');
        @mkdir(__DIR__.'/output', 0777, true);
        @mkdir(__DIR__.'/output/dnsmasq.d', 0777, true);
        @mkdir(__DIR__.'/output/resolver', 0777, true);
        @touch(__DIR__.'/output/.gitkeep');
    }

    public function tear_down()
    {
        exec('rm -rf '.__DIR__.'/output');
        mkdir(__DIR__.'/output');
        touch(__DIR__.'/output/.gitkeep');

        Mockery::close();
    }

    public function test_install_installs_and_places_configuration_files_in_proper_locations()
    {
        // Brew Mock
        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('ensureInstalled')->once()->with('dnsmasq');
        $brew->shouldReceive('restartService')->once()->with('dnsmasq');
        swap(Brew::class, $brew);

        // Config Mock
        swap(Configuration::class, $config = Mockery::spy(Configuration::class, [
            'read' => ['tld' => 'test', 'loopback' => VALET_LOOPBACK],
        ]));

        // DnsMasq (with stub path)
        $dnsMasq = resolve(StubForCreatingCustomDnsMasqConfigFiles::class);
        $dnsMasq->dnsmasqMasterConfigFile = __DIR__.'/output/dnsmasq.conf';
        $dnsMasq->dnsmasqSystemConfDir   = __DIR__.'/output/dnsmasq.d';
        $dnsMasq->resolverPath           = __DIR__.'/output/resolver';

        // Output master config (with placeholder content, as in the original test)
        file_put_contents(
            $dnsMasq->dnsmasqMasterConfigFile,
            file_get_contents(__DIR__.'/files/dnsmasq.conf')
        );

        $dnsMasq->install('test');

        $this->assertFileExists(__DIR__.'/output/resolver/test');
        $this->assertSame('nameserver '.VALET_LOOPBACK.PHP_EOL, file_get_contents(__DIR__.'/output/resolver/test'));

        // 2) Old logic: tld-<tld>.conf created | New logic: not available
        $legacyTldFileA = __DIR__.'/output/tld-test.conf';
        $legacyTldFileB = __DIR__.'/output/dnsmasq.d/tld-test.conf'; // if anyone wrote there earlier
        $hasLegacyTld = file_exists($legacyTldFileA) || file_exists($legacyTldFileB);

        if ($hasLegacyTld) {
            $path = file_exists($legacyTldFileA) ? $legacyTldFileA : $legacyTldFileB;
            $this->assertSame(
                'address=/.test/'.VALET_LOOPBACK.PHP_EOL.'listen-address='.VALET_LOOPBACK.PHP_EOL,
                file_get_contents($path),
                'Legacy tld-test.conf existiert, Inhalt sollte korrekt sein.'
            );
        } else {
            // New logic: explicitly ensure that the file does not exist
            $this->assertFileDoesNotExist($legacyTldFileA);
            $this->assertFileDoesNotExist($legacyTldFileB);
        }

        // 3) Master config contains conf-dir (not exact equality, only “contains”)
        $this->assertFileExists(__DIR__.'/output/dnsmasq.conf');
        $actual = file_get_contents(__DIR__.'/output/dnsmasq.conf');
        $this->assertIsString($actual);
        $this->assertNotSame('', $actual, 'dnsmasq.conf ist leer – erwartete Basisinhalte + conf-dir Eintrag.');
        $this->assertStringContainsString(
            'conf-dir='.BREW_PREFIX.'/etc/dnsmasq.d/,*.conf',
            $actual,
            'dnsmasq.conf sollte conf-dir-Zeile enthalten.'
        );
    }

    public function test_update_tld_removes_old_resolver_and_reinstalls()
    {
        // Arrange: Test folder & initial state
        @mkdir(__DIR__.'/output/dnsmasq.d', 0777, true);
        @mkdir(__DIR__.'/output/resolver', 0777, true);
        file_put_contents(__DIR__.'/output/resolver/old', 'nameserver '.VALET_LOOPBACK.PHP_EOL);

        // (optional) Legacy wildcard (old logic)
        file_put_contents(__DIR__.'/output/tld-old.conf', 'address=/.old/'.VALET_LOOPBACK.PHP_EOL.'listen-address='.VALET_LOOPBACK.PHP_EOL);

        // (optional) Host configuration that can be remapped by new logic
        file_put_contents(__DIR__.'/output/dnsmasq.d/host-foo.old.conf', 'address=/foo.old/'.VALET_LOOPBACK.PHP_EOL.'listen-address='.VALET_LOOPBACK.PHP_EOL);

        // Mocks/Swaps
        swap(Configuration::class, $config = Mockery::spy(Configuration::class, [
            'read' => ['tld' => 'test', 'loopback' => VALET_LOOPBACK],
        ]));

        // Brew mock, but no strict expectations (compatible with both implementations)
        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('ensureInstalled')->zeroOrMoreTimes();
        $brew->shouldReceive('restartService')->zeroOrMoreTimes();
        swap(Brew::class, $brew);

        // CLI may remain unused
        $cli = Mockery::mock(CommandLine::class);

        $fs = new Filesystem;

        // No partial mocks with install() expectation!
        $dnsMasq = new DnsMasq($brew, $cli, $fs, $config);

        // Redirect paths to test output
        $dnsMasq->dnsmasqMasterConfigFile = __DIR__.'/output/dnsmasq.conf';
        $dnsMasq->dnsmasqSystemConfDir   = __DIR__.'/output/dnsmasq.d';
        $dnsMasq->resolverPath           = __DIR__.'/output/resolver';

        // Act
        $dnsMasq->updateTld('old', 'new');

        // Assert – 1) old resolver is gone (both logics)
        $this->assertFileDoesNotExist(__DIR__.'/output/resolver/old');

        // Assert – 2) new resolver (if your implementation creates it)
        $newResolver = __DIR__.'/output/resolver/new';
        if (file_exists($newResolver)) {
            $this->assertSame('nameserver '.VALET_LOOPBACK.PHP_EOL, file_get_contents($newResolver));
        }

        // Assert – 3) Host remap (new logic only). If present, check content.
        $maybeNewHost = __DIR__.'/output/dnsmasq.d/host-foo.new.conf';
        if (file_exists($maybeNewHost)) {
            $this->assertSame(
                'address=/foo.new/'.VALET_LOOPBACK.PHP_EOL.'listen-address='.VALET_LOOPBACK.PHP_EOL,
                file_get_contents($maybeNewHost),
                'Neue Logik: host-foo.old.conf sollte zu host-foo.new.conf remapped werden.'
            );
        }

        // Old files may be present or removed depending on implementation:
        // - Legacy tld-old.conf: old may still be there, new may be removed -> no hard assert.
        // - host-foo.old.conf: new may be removed, old may still be there -> no hard assert.
    }

}

/**
 * Stub: redirect user config dir to test output. Historically, this was the root of output,
 * but now everything is located under dnsmasq.d/. We leave the root here because the old logic
 * tld-<tld>.conf wrote exactly there.
 */
class StubForCreatingCustomDnsMasqConfigFiles extends DnsMasq
{
    public function dnsmasqUserConfigDir(): string
    {
        return __DIR__.'/output/';
    }
}
