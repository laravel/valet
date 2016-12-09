# Laravel Valet

[![Build Status](https://travis-ci.org/laravel/valet.svg?branch=master)](https://travis-ci.org/laravel/valet)
[![Total Downloads](https://poser.pugx.org/laravel/valet/d/total.svg)](https://packagist.org/packages/laravel/valet)
[![Latest Stable Version](https://poser.pugx.org/laravel/valet/v/stable.svg)](https://packagist.org/packages/laravel/valet)
[![Latest Unstable Version](https://poser.pugx.org/laravel/valet/v/unstable.svg)](https://packagist.org/packages/laravel/valet)
[![License](https://poser.pugx.org/laravel/valet/license.svg)](https://packagist.org/packages/laravel/valet)

## Introduction

Valet is a Laravel development environment for Mac minimalists. No Vagrant, no `/etc/hosts` file. You can even share your sites publicly using local tunnels. _Yeah, we like it too._

Laravel Valet configures your Mac to always run Nginx in the background when your machine starts. Then, using [DnsMasq](https://en.wikipedia.org/wiki/Dnsmasq), Valet proxies all requests on the `*.dev` domain to point to sites installed on your local machine.

In other words, a blazing fast Laravel development environment that uses roughly 7mb of RAM. Valet isn't a complete replacement for Vagrant or Homestead, but provides a great alternative if you want flexible basics, prefer extreme speed, or are working on a machine with a limited amount of RAM.

## Official Documentation

Documentation for Valet can be found on the [Laravel website](https://laravel.com/docs/valet).

## Upgrading To Valet 2.0

Valet 2.0 transitions Valet's underlying web server from Caddy to Nginx. Before upgrading to this version you should run the following commands to stop and uninstall the existing Caddy daemon:

```
valet stop
valet uninstall
```

Next, you should upgrade to the latest version of Valet. Depending on how you installed Valet, this is typically done through Git or Composer. Once the fresh Valet source code has been downloaded, you should run the `install` command:

```
valet install
valet restart
```

After upgrading, it may be necessary to re-park or re-link your sites.

## License

Laravel Valet is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
