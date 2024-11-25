<p align="center"><img width="304" height="52" src="/art/logo.svg"></p>

<p align="center">
<a href="https://github.com/laravel/valet/actions?query=workflow%3ATests"><img src="https://github.com/laravel/valet/actions/workflows/tests.yml/badge.svg?branch=master" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/valet"><img src="https://poser.pugx.org/laravel/valet/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/valet"><img src="https://poser.pugx.org/laravel/valet/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/valet"><img src="https://poser.pugx.org/laravel/valet/license.svg" alt="License"></a>
</p>

## Introduction

Valet is a Laravel development environment for Mac minimalists. No Vagrant, no `/etc/hosts` file. You can even share your sites publicly using local tunnels. _Yeah, we like it too._

Laravel Valet configures your Mac to always run Nginx in the background when your machine starts. Then, using [DnsMasq](https://en.wikipedia.org/wiki/Dnsmasq), Valet proxies all requests on the `*.test` domain to point to sites installed on your local machine.

In other words, a blazing fast Laravel development environment that uses roughly 7mb of RAM. Valet isn't a complete replacement for Vagrant or Homestead, but provides a great alternative if you want flexible basics, prefer extreme speed, or are working on a machine with a limited amount of RAM.

## Official Documentation

Documentation for Valet can be found on the [Laravel website](https://laravel.com/docs/valet).

## Contributing

Thank you for considering contributing to Valet! You can read the contribution guide [here](.github/CONTRIBUTING.md).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/valet/security/policy) on how to report security vulnerabilities.

## License

Laravel Valet is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
