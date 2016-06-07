# Laravel Valet *for Ubuntu*

[![Build Status](https://travis-ci.org/cpriego/valet-ubuntu.svg?branch=master)](https://travis-ci.org/cpriego/valet-ubuntu)
[![Latest Stable Version](https://poser.pugx.org/cpriego/valet-ubuntu/v/stable)](https://packagist.org/packages/cpriego/valet-ubuntu)
[![Total Downloads](https://poser.pugx.org/cpriego/valet-ubuntu/downloads)](https://packagist.org/packages/cpriego/valet-ubuntu)
[![Latest Unstable Version](https://poser.pugx.org/cpriego/valet-ubuntu/v/unstable)](https://packagist.org/packages/cpriego/valet-ubuntu)
[![License](https://poser.pugx.org/cpriego/valet-ubuntu/license)](https://packagist.org/packages/cpriego/valet-ubuntu)

## Introduction

Valet *for Ubuntu* is a Laravel development environment for Ubuntu minimalists. No Vagrant, No Apache, No Nginx, No `/etc/hosts` file. You can even share your sites publicly using local tunnels. _Yeah, we like it too._

Laravel Valet *for Ubuntu* configures your system to always run [Caddy](https://caddyserver.com/) in the background when your machine starts. Then, using [DnsMasq](https://en.wikipedia.org/wiki/Dnsmasq), Valet proxies all requests on the `*.dev` domain to point to sites installed on your local machine.

In other words, a blazing fast Laravel development environment that uses roughly 7mb of RAM. Valet *for Ubuntu* isn't a complete replacement for Vagrant or Homestead, but provides a great alternative if you want flexible basics, prefer extreme speed, or are working on a machine with a limited amount of RAM.

## Official Documentation

Documentation for Valet can be found on the [Laravel website](http://laravel.com/docs/5.2/valet).

## Requirements

 - Ubuntu >= 15.04
 - Dependencies: `sudo apt-get install libnss3-tools jq xsel`
 - PHP >= 5.6
 - PHP Packages: `php*-cli php*-common php*-curl php*-json php*-mbstring php*-mcrypt php*-mysql php*-opcache php*-readline php*-xml php*-zip`
 - Optional PHP Packages: `php*-sqlite3 php*-mysql php*-pgsql`

**Replace the star _(*)_ with your php version**

## Installation

1. `composer global require cpriego/valet-ubuntu`
2. `valet install`

## Caveats

Because of the way Firefox and Chrome/Chromium/Opera/Any.Other.Blink.Based.Browser manages certificates in Linux the experience when **securing** a site might not be as smooth as it is in OSX.

Whenever you secure a site you'll need to restart your testing browser so that it can trust the new certificate and you'll have to do the same when you unsecure it.

If you have **secured** a domain you will not be able to share it through Ngrok.

## Usage

**`valet park`**

You can use `valet park` inside the directory where you store your projects (like Sites or Code) and then you can open `http://projectname.dev` in your browser. This command will allow you to access all the projects in the *parked* folder.

**`valet link`**

If you just want to serve a single site you can use `valet link [your-desired-url]` and then open `http://your-desired-url.dev` in the browser.

**`valet status`**

To check the status of the **Valet _for Ubuntu_** services.

## Update

To update your Valet package just run: `composer global update`

## F.A.Q.

**Why can't I run `valet install`?**

Check that you've added the `.composer/vendor/bin` directory to your `PATH` in either `~/.bashrc` or `~/.zshrc`.

**What about the Database?**

Well, your choice! You could use the superlight SQLite **`sqlite3`**, the extremely versatile MariaDB/MySQL **`mariadb-server or mysql-server`** or even the powerful PostgreSQL **`postgresql`**. Just don't forget to install the corresponding php package for it.

**Any other tips?**

Oh yeah!, for those looking for a beautiful looking Database management tool like Sequel Pro but for Linux* try out Valentina Studio, it's free, multiplatform and supports all of the databases mentioned above.

[You can check it here](https://www.valentina-db.com/en/valentina-studio-overview)

[And download it here](https://www.valentina-db.com/en/studio/download)

_* I know it is GNU/Linux but is too long and it confuses people even more_

## License

Laravel Valet is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
