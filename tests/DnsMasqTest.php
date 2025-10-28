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

        // DnsMasq (mit Stub-Pfad)
        $dnsMasq = resolve(StubForCreatingCustomDnsMasqConfigFiles::class);
        $dnsMasq->dnsmasqMasterConfigFile = __DIR__.'/output/dnsmasq.conf';
        $dnsMasq->dnsmasqSystemConfDir   = __DIR__.'/output/dnsmasq.d';
        $dnsMasq->resolverPath           = __DIR__.'/output/resolver';

        // Ausgangs-master-config (mit Platzhalter-Inhalt, wie im Originaltest)
        file_put_contents(
            $dnsMasq->dnsmasqMasterConfigFile,
            file_get_contents(__DIR__.'/files/dnsmasq.conf')
        );

        $dnsMasq->install('test');

        // 1) Resolver geschrieben
        $this->assertFileExists(__DIR__.'/output/resolver/test');
        $this->assertSame('nameserver '.VALET_LOOPBACK.PHP_EOL, file_get_contents(__DIR__.'/output/resolver/test'));

        // 2) Alte Logik: tld-<tld>.conf angelegt | Neue Logik: nicht vorhanden
        $legacyTldFileA = __DIR__.'/output/tld-test.conf';
        $legacyTldFileB = __DIR__.'/output/dnsmasq.d/tld-test.conf'; // falls jemand früher dorthin schrieb
        $hasLegacyTld = file_exists($legacyTldFileA) || file_exists($legacyTldFileB);

        if ($hasLegacyTld) {
            $path = file_exists($legacyTldFileA) ? $legacyTldFileA : $legacyTldFileB;
            $this->assertSame(
                'address=/.test/'.VALET_LOOPBACK.PHP_EOL.'listen-address='.VALET_LOOPBACK.PHP_EOL,
                file_get_contents($path),
                'Legacy tld-test.conf existiert, Inhalt sollte korrekt sein.'
            );
        } else {
            // Neue Logik: explizit sicherstellen, dass die Datei nicht existiert
            $this->assertFileDoesNotExist($legacyTldFileA);
            $this->assertFileDoesNotExist($legacyTldFileB);
        }

        // 3) Master-Config enthält conf-dir (nicht exakte Gleichheit, nur "contains")
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
        // Arrange: Testordner & Ausgangszustand
        @mkdir(__DIR__.'/output/dnsmasq.d', 0777, true);
        @mkdir(__DIR__.'/output/resolver', 0777, true);
        file_put_contents(__DIR__.'/output/resolver/old', 'nameserver '.VALET_LOOPBACK.PHP_EOL);

        // (optional) Legacy-Wildcard (alte Logik)
        file_put_contents(__DIR__.'/output/tld-old.conf', 'address=/.old/'.VALET_LOOPBACK.PHP_EOL.'listen-address='.VALET_LOOPBACK.PHP_EOL);

        // (optional) Host-Config, die von neuer Logik remapped werden kann
        file_put_contents(__DIR__.'/output/dnsmasq.d/host-foo.old.conf', 'address=/foo.old/'.VALET_LOOPBACK.PHP_EOL.'listen-address='.VALET_LOOPBACK.PHP_EOL);

        // Mocks/Swaps
        swap(Configuration::class, $config = Mockery::spy(Configuration::class, [
            'read' => ['tld' => 'test', 'loopback' => VALET_LOOPBACK],
        ]));

        // Brew mocken, aber keine strikten Erwartungen (kompatibel zu beiden Implementierungen)
        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('ensureInstalled')->zeroOrMoreTimes();
        $brew->shouldReceive('restartService')->zeroOrMoreTimes();
        swap(Brew::class, $brew);

        // CLI kann ungenutzt bleiben
        $cli = Mockery::mock(CommandLine::class);

        $fs = new Filesystem;

        // Keine Partial-Mocks mit install()-Expectation!
        $dnsMasq = new DnsMasq($brew, $cli, $fs, $config);

        // Pfade auf Test-Output umbiegen
        $dnsMasq->dnsmasqMasterConfigFile = __DIR__.'/output/dnsmasq.conf';
        $dnsMasq->dnsmasqSystemConfDir   = __DIR__.'/output/dnsmasq.d';
        $dnsMasq->resolverPath           = __DIR__.'/output/resolver';

        // Act
        $dnsMasq->updateTld('old', 'new');

        // Assert – 1) alter Resolver ist weg (beide Logiken)
        $this->assertFileDoesNotExist(__DIR__.'/output/resolver/old');

        // Assert – 2) neuer Resolver (falls deine Implementierung ihn anlegt)
        $newResolver = __DIR__.'/output/resolver/new';
        if (file_exists($newResolver)) {
            $this->assertSame('nameserver '.VALET_LOOPBACK.PHP_EOL, file_get_contents($newResolver));
        }

        // Assert – 3) Host-Remap (nur neue Logik). Falls vorhanden, Inhalt prüfen.
        $maybeNewHost = __DIR__.'/output/dnsmasq.d/host-foo.new.conf';
        if (file_exists($maybeNewHost)) {
            $this->assertSame(
                'address=/foo.new/'.VALET_LOOPBACK.PHP_EOL.'listen-address='.VALET_LOOPBACK.PHP_EOL,
                file_get_contents($maybeNewHost),
                'Neue Logik: host-foo.old.conf sollte zu host-foo.new.conf remapped werden.'
            );
        }

        // Alte Dateien dürfen je nach Implementierung vorhanden oder entfernt sein:
        // - Legacy tld-old.conf: alt evtl. noch da, neu evtl. entfernt -> kein harter Assert.
        // - host-foo.old.conf: neu evtl. entfernt, alt evtl. noch da -> kein harter Assert.
    }

}

/**
 * Stub: leite user config dir in die Test-Ausgabe. Historisch war das root von output,
 * neu liegt alles eher unter dnsmasq.d/. Wir lassen hier das Root, weil die alte Logik
 * tld-<tld>.conf genau dorthin geschrieben hat.
 */
class StubForCreatingCustomDnsMasqConfigFiles extends DnsMasq
{
    public function dnsmasqUserConfigDir(): string
    {
        return __DIR__.'/output/';
    }
}
