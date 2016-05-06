<?php

namespace Valet;

class CompatibilityLinuxSystemd
{
    const WHICH_INSTALLER = 'which apt-get';
    const WHICH_INSTALLER_PATH = '/usr/bin/apt-get';
    const WHICH_INSTALLER_ERROR = 'Valet requires apt-get to be installed on your Linux.';

    const NGROK_INSTALL = 'https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-amd64.zip';
    const NGROK_UNZIP = 'unzip %s';
    const NGROK_INSTALL_TEXT = '<info>ngrok is not installed, downloading and unzipping it...</info>';

    const VALET_HOME_PATH = '/home/%s/.valet';
    const DNSMASQ_INSTALL = 'sudo -u %s apt-get install -y dnsmasq';
    const DNSMASQ_INSTALL_TEXT = '<info>DnsMasq is not installed, installing it now via apt...</info>';
    const DNSMASQ_ALREADY_INSTALLED = 'which dnsmasq';
    const DNSMASQ_RESTART = 'sudo service dnsmasq restart';
    const DNSMASQ_USER = '/root/.valet/dnsmasq.conf';
    const DNSMASQ_CONF_EXAMPLE = '/etc/dnsmasq.conf';
    const DNSMASQ_CONF = '/etc/dnsmasq.conf';
    
    const LAUNCH_DAEMON_INSTALL_SCRIPT = '/../stubs/linux.systemd';
    const LAUNCH_DAEMON_INSTALL_PATH = '/etc/systemd/system/laravel-valetd.service';
    
    const LAUNCH_DAEMON_QUIETLY_START = 'systemctl start laravel-valetd > /dev/null';
    const LAUNCH_DAEMON_QUIETLY_RESTART = '';
    const LAUNCH_DAEMON_RESTART = 'systemctl stop laravel-valetd && systemctl start laravel-valetd';
    const LAUNCH_DAEMON_STOP = 'systemctl stop laravel-valetd';
    const LAUNCH_DAEMON_UNLINK = '/etc/systemd/system/laravel-valetd.service';
}
