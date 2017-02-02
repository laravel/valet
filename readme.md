# Valet *Linux*

[![Build Status](https://travis-ci.org/cpriego/valet-linux.svg?branch=master)](https://travis-ci.org/cpriego/valet-linux)
[![Total Downloads](https://poser.pugx.org/cpriego/valet-linux/downloads.svg)](https://packagist.org/packages/cpriego/valet-linux)
[![Latest Stable Version](https://poser.pugx.org/cpriego/valet-linux/v/stable.svg)](https://packagist.org/packages/cpriego/valet-linux)
[![Latest Unstable Version](https://poser.pugx.org/cpriego/valet-linux/v/unstable.svg)](https://packagist.org/packages/cpriego/valet-linux)
[![License](https://poser.pugx.org/cpriego/valet-linux/license.svg)](https://packagist.org/packages/cpriego/valet-linux)

## Introduction

Valet *Linux* is a Laravel development environment for Linux minimalists. No Vagrant, no `/etc/hosts` file. You can even share your sites publicly using local tunnels. _Yeah, we like it too._

Valet *Linux* configures your system to always run Nginx in the background when your machine starts. Then, using [DnsMasq](https://en.wikipedia.org/wiki/Dnsmasq), Valet proxies all requests on the `*.dev` domain to point to sites installed on your local machine.

In other words, a blazing fast Laravel development environment that uses roughly 7mb of RAM. Valet *Linux* isn't a complete replacement for Vagrant or Homestead, but provides a great alternative if you want flexible basics, prefer extreme speed, or are working on a machine with a limited amount of RAM.

## Official Documentation

Documentation for Valet can be found on the [Laravel website](https://laravel.com/docs/valet).

## Upgrading To Valet 2.0.*

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

## Requirements
 - PHP >= 5.6
 - PHP Packages: `php*-cli php*-curl php*-mbstring php*-mcrypt php*-xml php*-zip`
 - Optional PHP Packages: `php*-sqlite3 php*-mysql php*-pgsql`

**Replace the star _(*)_ with your php version**

### Ubuntu
 - Ubuntu >= 14.04
 - Ubuntu 
 - Dependencies: `sudo apt-get install libnss3-tools jq xsel`

Regarding the **supported** Ubuntu version:
 - LTS. Will get 4 year support (meaning only the latest 2 releases).
 - Non-LTS. Only get 9 months. You *should* update anyway.
 - Development. Only if I have the time. Development version are extremely unstable and prone to change. It is **very** difficult to isolate any issue.

### Fedora
 - Fedora >= 24
 - Dependencies: `dnf install nss-tools jq xsel`

Valet *Linux* expects a `sudo` user with the `$HOME` environment variable set. Fedora users are *expected* to have knowledge of SELinux and how to configure it or disable it while Valet makes changes to the configuration files.

To set the `$HOME` environment variable when using `sudo` in Fedora: 
 - Open your sudoers file with `visudo`
 - Find the lines with the text `Defaults    env_keep += `
 - Append `Defaults    env_keep += "HOME"` after those lines
 - Save your changes

#### SELinux Permissive Mode
Temporarily (until reboot): `sudo setenforce 0`

Permanent:
 - Open `/etc/selinux/config`
 - Change `SELINUX=enforcing` to `SELINUX=permissive`
 - Reboot

## Installation

1. `composer global require cpriego/valet-linux`
2. `valet install`

## Caveats

### SSL

Because of the way Firefox and Chrome/Chromium/Opera/Any.Other.Blink.Based.Browser manages certificates in Linux the experience when **securing** a site might not be as smooth as it is in OSX.

Whenever you secure a site you'll need to restart your testing browser so that it can trust the new certificate and you'll have to do the same when you unsecure it.

If you have **secured** a domain you will not be able to share it through Ngrok.

### Nginx, PHP-FPM

Valet 2.0 will overwrite the Nginx, PhpFPM config files. If you've previously configured Nginx please backup your files before upgrading.

### DnsMasq and NetworkManager

**NetworkManager** loves being involved in everything network-related including DNS. We configure **DnsMasq** through **NetworkManager** so your network connection _**might**_ drop whenever you **install** Valet or change the domain. To solve this simply reconnect to your network.

## Usage

**`valet park`**

You can use `valet park` inside the directory where you store your projects (like Sites or Code) and then you can open `http://projectname.dev` in your browser. This command will allow you to access all the projects in the *parked* folder.

**`valet link`**

If you just want to serve a single site you can use `valet link [your-desired-url]` and then open `http://your-desired-url.dev` in the browser.

**`valet status`**

To check the status of the **Valet _for Linux_** services.

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
