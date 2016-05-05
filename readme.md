## Laravel Valet

Laravel development environment for Mac minimalists.

### What Is It?

Laravel Valet configures your Mac to always run PHP's built-in web server in the background when your machine starts. Then, using DnsMasq, Valet proxies all requests on the `*.dev` domain to point to sites installed on your local machine. In other words, a blazing fast PHP development environment that uses roughly 7mb of RAM. No Apache, No Nginx.

### Getting Started

**Requirements**

Valet requires the Mac operating system and [Homebrew](http://brew.sh/).

1. Install or update [Homebrew](http://brew.sh/) to the latest version.
2. Make sure `brew services` is available by running `brew services list` and making sure you get valid output. If it is not available, [add it](https://github.com/Homebrew/homebrew-services).
3. Install PHP 7.0 via Homebrew via `brew install php70`.
4. Install Valet `composer global require laravel/valet`.
5. Run the `valet install` command. This will configure and install Valet, DnsMasq, and register Valet's daemon to launch when your system starts.



