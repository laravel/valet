<?php

namespace Valet;

class CompatibilityDarwin
{
    const VALET_HOME_PATH = '/Users/%s/.valet';
    const DNSMASQ_INSTALL = 'sudo -u %s apt-get install dnsmasq';
    const DNSMASQ_INSTALL_TEXT = '<info>DnsMasq is not installed, installing it now via apt...</info>';
    const DNSMASQ_ALREADY_INSTALLED = 'which dnsmasq';
    const DNSMASQ_RESTART = 'sudo service dnsmasq restart';
    const DNSMASQ_ROOT_USER = '/%s/.valet/dnsmasq.conf';
    const DNSMASQ_CONF_EXAMPLE = '/etc/dnsmasq.conf';
    const DNSMASQ_CONF = '/etc/dnsmasq.conf';

    const LAUNCH_DAEMON_INSTALL_SCRIPT = '/../stubs/daemon.plist';
    const LAUNCH_DAEMON_INSTALL_PATH = '/Library/LaunchDaemons/com.laravel.valetServer.plist';

    const LAUNCH_DAEMON_QUIETLY_START = '';
    const LAUNCH_DAEMON_QUIETLY_RESTART = 'launchctl unload /Library/LaunchDaemons/com.laravel.valetServer.plist > /dev/null';
    const LAUNCH_DAEMON_RESTART = 'launchctl load /Library/LaunchDaemons/com.laravel.valetServer.plist';
    const LAUNCH_DAEMON_STOP = 'systemctl stop laravel-valetd';
    const LAUNCH_DAEMON_UNLINK = '/etc/systemd/system/laravel-valetd.service';
}