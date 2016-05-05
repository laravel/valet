## Laravel Valet

Laravel development environment for Mac minimalists. No Vagrant, No Apache, No Nginx, No `/etc/hosts` file.

### What Is It?

Laravel Valet configures your Mac to always run PHP's built-in web server in the background when your machine starts. Then, using DnsMasq, Valet proxies all requests on the `*.dev` domain to point to sites installed on your local machine. In other words, a blazing fast PHP development environment that uses roughly 7mb of RAM. No Apache, No Nginx, No `/etc/hosts` file.

### Getting Started

#### Installation

Valet requires the Mac operating system and [Homebrew](http://brew.sh/).

1. Install or update [Homebrew](http://brew.sh/) to the latest version.
2. Make sure `brew services` is available by running `brew services list` and making sure you get valid output. If it is not available, [add it](https://github.com/Homebrew/homebrew-services).
3. Install PHP 7.0 via Homebrew via `brew install php70`.
4. Install Valet `composer global require laravel/valet`.
5. Run the `valet install` command. This will configure and install Valet, DnsMasq, and register Valet's daemon to launch when your system starts.

Once Valet is installed, try pinging any `*.dev` domain on your terminal using a command such as `ping foobar.dev`. If Valet is installed correctly you should see this domain responding on `127.0.0.1`.

#### Serving Sites

Once Valet is installed, you're ready to start serving sites. Valet provides two commands to help you serve your Laravel sites: `park` and `link`.

First, let's try the `park` command:

- Create a new directory on your Mac such `mkdir ~/Sites`. Next, `cd ~/Sites` and run `valet park`. This command will register your current working directory as a path that Valet should search for sites.
- Next, create a new Laravel site within this directory: `laravel new blog`.
- Now you may simply open `http://blog.dev` in your browser.

**It's just that simple.** Any Laravel project you create within your "parked" directory will automatically be served using the `http://folder-name.dev` convention.




