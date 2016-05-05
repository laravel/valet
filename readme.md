# Laravel Valet

- [Introduction](#introduction)
- [Installation](#installation)
- [Serving Sites](#serving-sites)
    - [The "Park" Command](#the-park-command)
    - [The "Link" Command](#the-link-command)
- [Sharing Sites](#sharing-sites)
- [Viewing Logs](#viewing-logs)
- [Other Useful Commands](#other-useful-commands)

<a name="what-is-it"></a>
## Introduction

Valet is a Laravel development environment for Mac minimalists. No Vagrant, No Apache, No Nginx, No `/etc/hosts` file. You can even share your sites publicly using local tunnels. _Yeah, we like it too._

Laravel Valet configures your Mac to always run PHP's built-in web server in the background when your machine starts. Then, using DnsMasq, Valet proxies all requests on the `*.dev` domain to point to sites installed on your local machine.

In other words, a blazing fast Laravel development environment that uses roughly 7mb of RAM. Valet isn't a complete replacement for Vagrant or Homestead, but provides a great alternative if you just need the basics, prefer extreme speed, or are working on a machine with a limited amount of RAM.

Valet supports [Laravel](https://laravel.com), [Lumen](https://lumen.laravel.com), and [Statamic](https://statamic.com/).

<a name="installation"></a>
## Installation

**Valet requires the Mac operating system and [Homebrew](http://brew.sh/). Before installation, you should make sure that no other programs such as Apache or Nginx are binding to your local machine's port 80.**

1. Install or update [Homebrew](http://brew.sh/) to the latest version.
2. Make sure `brew services` is available by running `brew services list` and making sure you get valid output. If it is not available, [add it](https://github.com/Homebrew/homebrew-services).
3. Install PHP 7.0 via Homebrew via `brew install php70`.
4. Install Valet with Composer via `composer global require laravel/valet`. Make sure the `~/.composer/bin` directory is in your system's "PATH".
5. Run the `valet install` command. This will configure and install Valet, DnsMasq, and register Valet's daemon to launch when your system starts.

Once Valet is installed, try pinging any `*.dev` domain on your terminal using a command such as `ping foobar.dev`. If Valet is installed correctly you should see this domain responding on `127.0.0.1`.

Valet will automatically start its daemon each time your machine boots. There is no need to run `valet start` or `valet install` ever again once the initial Valet installation is complete.

#### Database

If you need a database, try MariaDB by running `brew install mariadb` on your command line. You can connect to the database at `127.0.0.1` using the `root` username and an empty string for the password.

<a name="serving-sites"></a>
## Serving Sites

Once Valet is installed, you're ready to start serving sites. Valet provides two commands to help you serve your Laravel sites: `park` and `link`.

<a name="the-park-command"></a>
**The `park` Command**

- Create a new directory on your Mac such `mkdir ~/Sites`. Next, `cd ~/Sites` and run `valet park`. This command will register your current working directory as a path that Valet should search for sites.
- Next, create a new Laravel site within this directory: `laravel new blog`.
- Now you may simply open `http://blog.dev` in your browser.

**It's just that simple.** Now, any Laravel project you create within your "parked" directory will automatically be served using the `http://folder-name.dev` convention.

<a name="the-link-command"></a>
**The `link` Command**

The `link` command may also be used to serve your Laravel sites. This command is useful if you just want to serve a single site in a directory and not the entire directory.

- To use the command, navigate to one of your Laravel applications and run `valet link app-name` in your terminal. Valet will create a symbolic link in `~/.valet/Sites` which points to your current working directory.
- After running the `link` command, you may simply access the site in your browser at `http://app-name.dev`.

To see a listing of all of your linked directories, run the `valet links` command. You may use `valet unlink app-name` to destroy the symbolic link.

<a name="sharing-sites"></a>
## Sharing Sites

Valet even includes a command to share your local sites with the world. No additional software installation is required once Valet is installed.

To share a site, simply navigate to the site and run the `valet share` command. A publicly accessible URL will be inserted into your clipboard and is ready to paste directly into your browser. It's just that simple.

To stop sharing your site, simply hit `Control + C` to cancel the process.

<a name="viewing-logs"></a>
## Viewing Logs

If you would like to stream all of the logs for all of your sites to your terminal, run the `valet logs` command. New log entries will display in your terminal as they occur. Squash those bugs!

<a name="other-useful-commands"></a>
## Other Useful Commands

Command  | Description
------------- | -------------
`valet forget` | Run this command from a "parked" directory to remove it from the parked directory list.
`valet paths` | View all of your "parked" paths.
`valet prune` | Remove paths that no longer exist from your "parked" paths.
`valet restart` | Restart the Valet daemon.
`valet start` | Start the Valet daemon.
`valet stop` | Stop the Valet daemon.
`valet uninstall` | Uninstall the Valet daemon entirely.
