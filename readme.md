## Laravel Valet

Laravel development environment for Mac minimalists.

No Vagrant, No Apache, No Nginx, No `/etc/hosts` file. You can even stream all of your logs and share your sites publicly using local tunnels. Yeah, we like it too.

- [What Is It?](#what-is-it)
- [Installation](#installation)
- [Serving Sites](#serving-sites)
    - [The "Park" Command](#the-park-command)
    - [The "Link" Command](#the-link-command)

<a name="what-is-it"></a>
### What Is It?

Laravel Valet configures your Mac to always run PHP's built-in web server in the background when your machine starts. Then, using DnsMasq, Valet proxies all requests on the `*.dev` domain to point to sites installed on your local machine. In other words, a blazing fast PHP development environment that uses roughly 7mb of RAM. No Apache, No Nginx, No `/etc/hosts` file.

<a name="installation"></a>
### Installation

Valet requires the Mac operating system and [Homebrew](http://brew.sh/).

1. Install or update [Homebrew](http://brew.sh/) to the latest version.
2. Make sure `brew services` is available by running `brew services list` and making sure you get valid output. If it is not available, [add it](https://github.com/Homebrew/homebrew-services).
3. Install PHP 7.0 via Homebrew via `brew install php70`.
4. Install Valet `composer global require laravel/valet`.
5. Run the `valet install` command. This will configure and install Valet, DnsMasq, and register Valet's daemon to launch when your system starts.

Once Valet is installed, try pinging any `*.dev` domain on your terminal using a command such as `ping foobar.dev`. If Valet is installed correctly you should see this domain responding on `127.0.0.1`.

<a name="serving-sites"></a>
### Serving Sites

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





