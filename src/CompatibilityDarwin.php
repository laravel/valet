<?php

namespace Valet;

class CompatibilityDarwin
{
    const WHICH_INSTALLER = 'which brew';
    const WHICH_INSTALLER_PATH = '/usr/local/bin/brew';
    const WHICH_INSTALLER_ERROR = 'Valet requires Brew to be installed on your Mac.';

    const VALET_HOME_PATH = '/Users/%s/.valet';
    const DNSMASQ_INSTALL = 'sudo -u %s brew install dnsmasq';
    const DNSMASQ_INSTALL_TEXT = '<info>DnsMasq is not installed, installing it now via Brew...</info> 🍻';
    const DNSMASQ_ALREADY_INSTALLED = 'brew list | grep dnsmasq';
    const DNSMASQ_RESTART = 'sudo brew services restart dnsmasq';
    const DNSMASQ_ROOT_USER = '/Users/%s/.valet/dnsmasq.conf';
    const DNSMASQ_CONF_EXAMPLE = '/usr/local/opt/dnsmasq/dnsmasq.conf.example';
    const DNSMASQ_CONF = '/usr/local/etc/dnsmasq.conf';

    const LAUNCH_DAEMON_INSTALL_SCRIPT = '/../stubs/daemon.plist';
    const LAUNCH_DAEMON_INSTALL_PATH = '/Library/LaunchDaemons/com.laravel.valetServer.plist';

    const LAUNCH_DAEMON_QUIETLY_START = '';
    const LAUNCH_DAEMON_QUIETLY_RESTART = 'launchctl unload /Library/LaunchDaemons/com.laravel.valetServer.plist > /dev/null';
    const LAUNCH_DAEMON_RESTART = 'launchctl load /Library/LaunchDaemons/com.laravel.valetServer.plist';
    const LAUNCH_DAEMON_STOP = 'systemctl stop laravel-valetd';
    const LAUNCH_DAEMON_UNLINK = '/etc/systemd/system/laravel-valetd.service';
}
