# Release Notes

## [Unreleased](https://github.com/laravel/valet/compare/v3.3.2...master)

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
