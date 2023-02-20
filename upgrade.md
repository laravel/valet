# Upgrading to v4

- You must have PHP 8.0+ installed, and set as your primary/linked PHP install when you install v4; you can use 7.4+ as your primary after that, but need to keep a version of 8+ installed at all times
- You must run `valet` once for the upgrader to run; this will attempt to update all of your configuration items and custom local drivers for v4
- If you have any issues with your drivers, all custom drivers (including the `SampleValetDriver` published by previous versions of Valet) must have the following; see the [new SampleValetDriver](https://github.com/laravel/valet/blob/d7787c025e60abc24a5195dc7d4c5c6f2d984339/cli/stubs/SampleValetDriver.php) for an example:
    - Match the new type hints of the base ValetDriver
    - Extend the new namespaced drivers instead of the old globally-namespaced drivers
    - Have their own namespace
