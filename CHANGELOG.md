# Release Notes

## [Unreleased](https://github.com/laravel/valet/compare/v4.7.0...master)

## [v4.7.0](https://github.com/laravel/valet/compare/v4.6.3...v4.7.0) - 2024-06-18

* Trust CA Certificate only by [@adrum](https://github.com/adrum) in https://github.com/laravel/valet/pull/1463
* Allow specifying php version to restart by [@N-Silbernagel](https://github.com/N-Silbernagel) in https://github.com/laravel/valet/pull/1469
* Update KirbyValetDriver.php by [@Werbschaft](https://github.com/Werbschaft) in https://github.com/laravel/valet/pull/1471
* fix: Warning: Constant VALET_* already defined in X by [@NickSdot](https://github.com/NickSdot) in https://github.com/laravel/valet/pull/1485
* Add values() call to reset keys after modifying paths by [@adriaanzon](https://github.com/adriaanzon) in https://github.com/laravel/valet/pull/1477
* Adding support for Nette Framework. by [@antonL95](https://github.com/antonL95) in https://github.com/laravel/valet/pull/1486

## [v4.6.3](https://github.com/laravel/valet/compare/v4.6.2...v4.6.3) - 2024-05-21

* add server name to valet.conf by [@Oleg339](https://github.com/Oleg339) in https://github.com/laravel/valet/pull/1458

## [v4.6.2](https://github.com/laravel/valet/compare/v4.6.1...v4.6.2) - 2024-05-07

* Add quotes around $PHP by [@drbyte](https://github.com/drbyte) in https://github.com/laravel/valet/pull/1473
* Support Illuminate:11 and Symfony:7 by [@drbyte](https://github.com/drbyte) in https://github.com/laravel/valet/pull/1481
* Ignore invalid paths by [@ahmedash95](https://github.com/ahmedash95) in https://github.com/laravel/valet/pull/1478

## [v4.6.1](https://github.com/laravel/valet/compare/v4.6.0...v4.6.1) - 2024-01-01

* Allow renew() to be called without parameters by [@drbyte](https://github.com/drbyte) in https://github.com/laravel/valet/pull/1465

## [v4.6.0](https://github.com/laravel/valet/compare/v4.5.0...v4.6.0) - 2023-12-27

* Bandage for 502 errors using Gettext/PostgreSQL/MongoDB by [@drbyte](https://github.com/drbyte) in https://github.com/laravel/valet/pull/1452
* handle deprecated http2_push_preload conf for nginx >= 1.25.1 by [@OpJePl44tsm4n](https://github.com/OpJePl44tsm4n) in https://github.com/laravel/valet/pull/1451
* Update "fetch-share-url" to pull correct URL for HTTPS tunnels, not just HTTP by [@rana01645](https://github.com/rana01645) in https://github.com/laravel/valet/pull/1460
* added the ability to renew certs and view their expiration dates by [@adrum](https://github.com/adrum) in https://github.com/laravel/valet/pull/1461

## [v4.5.0](https://github.com/laravel/valet/compare/v4.4.1...v4.5.0) - 2023-10-10

- Add support for proxying multiple domains at once by [@RobertBoes](https://github.com/RobertBoes) in https://github.com/laravel/valet/pull/1437

## [v4.4.1](https://github.com/laravel/valet/compare/v4.4.0...v4.4.1) - 2023-10-03

- Fix magento2 driver by [@mischabraam](https://github.com/mischabraam) in https://github.com/laravel/valet/pull/1420

## [v4.4.0](https://github.com/laravel/valet/compare/v4.3.0...v4.4.0) - 2023-09-26

- Allow LaravelValetDriver to serve other /public/*.php files by [@drbyte](https://github.com/drbyte) in https://github.com/laravel/valet/pull/1439
- Support static caching in Statamic by [@jasonvarga](https://github.com/jasonvarga) in https://github.com/laravel/valet/pull/1440
- Fix link command's `--isolate` argument with custom name by [@mcaskill](https://github.com/mcaskill) in https://github.com/laravel/valet/pull/1428

## [v4.3.0](https://github.com/laravel/valet/compare/v4.1.4...v4.3.0) - 2023-09-05

- Add "valet stop dnsmasq" option by [@drbyte](https://github.com/drbyte) in https://github.com/laravel/valet/pull/1422
- Drop Mailhog and Redis from default logs list, since Valet doesn't install them by [@mattstauffer](https://github.com/mattstauffer) in https://github.com/laravel/valet/pull/1438

## [v4.1.4](https://github.com/laravel/valet/compare/v4.1.3...v4.1.4) - 2023-08-14

### What's Changed

- Only delete DNSMasq resolver when Herd is not installed by [@mpociot](https://github.com/mpociot) in https://github.com/laravel/valet/pull/1434

**Full Changelog**: https://github.com/laravel/valet/compare/v4.1.3...v4.1.4

## [v4.1.3](https://github.com/laravel/valet/compare/v4.1.2...v4.1.3) - 2023-07-21

- Add quotes around $PHP by [@datashaman](https://github.com/datashaman) in https://github.com/laravel/valet/pull/1431

## [v4.1.2](https://github.com/laravel/valet/compare/v4.1.1...v4.1.2) - 2023-05-30

- Load specific drivers before custom drivers to allow for extension. by @mattstauffer in https://github.com/laravel/valet/pull/1414
- Ensure base configuration is correct even if file already exists by @mattstauffer in https://github.com/laravel/valet/pull/1415

## [v4.1.1](https://github.com/laravel/valet/compare/v4.1.0...v4.1.1) - 2023-05-16

- Install older PHP versions via alternate tap by @drbyte in https://github.com/laravel/valet/pull/1411

## [v4.1.0](https://github.com/laravel/valet/compare/v4.0.2...v4.1.0) - 2023-05-09

- Add driver for Radicle by @csorrentino in https://github.com/laravel/valet/pull/1413

## [v4.0.2](https://github.com/laravel/valet/compare/v4.0.1...v4.0.2) - 2023-04-26

- Fix ngrok.io proxy/forwarding detection by @drbyte in https://github.com/laravel/valet/pull/1404
- Fix read non existing config file by @mischabraam in https://github.com/laravel/valet/pull/1398

## [v4.0.1](https://github.com/laravel/valet/compare/v4.0.0...v4.0.1) - 2023-03-27

- Fixes Valet\Drivers\LocalValetDriver not found error by @fylzero in https://github.com/laravel/valet/pull/1388
- More robust check for Bedrock environments by @ethanclevenger91 in https://github.com/laravel/valet/pull/1390

## [v4.0.0](https://github.com/laravel/valet/compare/v3.3.2...v4.0.0) - 2023-03-14

Welcome to Valet v4! This release is mostly about re-writing the internals so they're easier to debug, fix, and modify. There are a few user-facing additions and improvements, including but not limited to: ngrok is now managed by Homebrew, `.valetphprc` is replaced with a more powerful `.valetrc`, you can use [Expose](https://expose.dev/) to share, there's a new `status` command to make sure all your services are running correctly, and a lot of the other existing commands work even better than ever before.

### Added

- Add Expose support by @mattstauffer in https://github.com/laravel/valet/pull/1344 and https://github.com/laravel/valet/pull/1349
- Add status command by @mattstauffer in https://github.com/laravel/valet/pull/1329
- Check whether services are running as the correct user in `valet status` by @mattstauffer in https://github.com/laravel/valet/pull/1348
- Add the ability for drivers to check Composer dependencies by @mattstauffer in https://github.com/laravel/valet/pull/1345
- Add php isolation from link command by @joelbutcher in https://github.com/laravel/valet/pull/1360

### Changed

- Replace `.valetphprc. with `.valetrc` by @mattstauffer in https://github.com/laravel/valet/pull/1347
- Update php and composer commands to allow passing in specific site by @mattstauffer in https://github.com/laravel/valet/pull/1370
- Require PHP 8.0 to be installed, but support PHP 7.1+ for isolated sites (https://github.com/laravel/valet/pull/1328 and https://github.com/laravel/valet/pull/1346)
- Re-work how BasicValetDriver serves files in projects with and without `public/` directory by @mattstauffer in https://github.com/laravel/valet/pull/1311
- Extract Server class and refactor loading of drivers by @mattstauffer in https://github.com/laravel/valet/pull/1319
- Add type hints and return types by @mattstauffer in https://github.com/laravel/valet/pull/1321
- Drop unnecessary doc blocks by @mattstauffer in https://github.com/laravel/valet/pull/1339
- Add CLI command tests by @mattstauffer in https://github.com/laravel/valet/pull/1332 and https://github.com/laravel/valet/pull/1335
- Implement `valet fetch-share-url` when working with Expose by @mattstauffer in
- Use Pint for Code Styling by @driesvints in https://github.com/laravel/valet/pull/1366
- Release Version 4 by @mattstauffer in https://github.com/laravel/valet/pull/1318, https://github.com/laravel/valet/pull/1365

### Fixed

- Unsecure when unlinking by @mattstauffer in https://github.com/laravel/valet/pull/1364
- Update phpRc reader to check cwd before checking config, if cwd specified by @mattstauffer in https://github.com/laravel/valet/pull/1361
- Remove Valet Certificate Authority on uninstall by @mattstauffer in https://github.com/laravel/valet/pull/1358

## [v3.3.2](https://github.com/laravel/valet/compare/v3.3.1...v3.3.2) - 2023-02-07

### Fixed

- Enable local network sharing by @thinkverse in https://github.com/laravel/valet/pull/1284

## [v3.3.1](https://github.com/laravel/valet/compare/v3.3.0...v3.3.1) - 2023-01-31

### Fixed

- Make 127.0.0.1 SERVER_ADDR definition only if null by @mattstauffer in https://github.com/laravel/valet/pull/1356

## [v3.3.0](https://github.com/laravel/valet/compare/v3.2.2...v3.3.0) - 2023-01-17

### Added

- Add `set-ngrok-token` command by @mattstauffer in https://github.com/laravel/valet/pull/1325
- Laravel v10 Support by @driesvints in https://github.com/laravel/valet/pull/1341

### Fixed

- Fixes permission denied check when result starts with 'Permission denied' by @matthewjohns0n in https://github.com/laravel/valet/pull/1343

## [v3.2.2](https://github.com/laravel/valet/compare/v3.2.1...v3.2.2) - 2022-12-08

### Fixed

- Fix how drivers are available for extension by @mattstauffer in https://github.com/laravel/valet/pull/1317

## [v3.2.1](https://github.com/laravel/valet/compare/v3.2.0...v3.2.1) - 2022-12-06

### Fixed

- Fix autoloading in distribution by @mattstauffer in https://github.com/laravel/valet/commit/35998a0f86b9d57b5766f2663a41a2c99af3d579

## [v3.2.0](https://github.com/laravel/valet/compare/v3.1.13...v3.2.0) - 2022-12-06

### Changed

- When formatting provided site name, only remove .tld if it's at the end by @ErikDohmen in https://github.com/laravel/valet/pull/1297
- Set files path as a static path for concrete5 driver by @KorvinSzanto in https://github.com/laravel/valet/pull/514
- Allow users to provide custom stub files by @jjpmann in https://github.com/laravel/valet/pull/1238
- Disable brew auto cleanup on installation, to avoid upgrade errors failing install by @JoshuaBehrens in https://github.com/laravel/valet/pull/995
- Move all drivers to PSR autoload, and write tests for drivers by @mattstauffer in https://github.com/laravel/valet/pull/1310

### Fixed

- Retain secure proxies by @ashleyshenton in https://github.com/laravel/valet/pull/1305
- Prevent 502 errors when using AJAX by @mattkingshott in https://github.com/laravel/valet/pull/1079

## [v3.1.13](https://github.com/laravel/valet/compare/v3.1.12...v3.1.13) - 2022-11-15

### Fixed

- Fix bedrockvaletdriver by @EHLOVader in https://github.com/laravel/valet/pull/1289

## [v3.1.12](https://github.com/laravel/valet/compare/v3.1.11...v3.1.12) - 2022-10-25

### Changed

- Valet fetch-share-url issue fix by @NasirNobin in https://github.com/laravel/valet/pull/1285
- Add description to `secured` command by @EvanGoss in https://github.com/laravel/valet/pull/1288

## [v3.1.11](https://github.com/laravel/valet/compare/v3.1.9...v3.1.11) - 2022-09-08

### Fixed

- Update version by @driesvints in https://github.com/laravel/valet/commit/6f1e4d421bfa7a206133e971a186de708c963adf
