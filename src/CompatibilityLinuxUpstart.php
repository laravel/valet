<?php

namespace Valet;


class CompatibilityLinuxUpstart extends CompatibilityLinuxSystemd
{
    const LAUNCH_DAEMON_INSTALL_SCRIPT = '/../stubs/linux.upstart';
    const LAUNCH_DAEMON_INSTALL_PATH = '/etc/init/laravel-valetd.conf';

    const LAUNCH_DAEMON_QUIETLY_START = 'service laravel-valetd start';
    const LAUNCH_DAEMON_QUIETLY_RESTART = '';
    const LAUNCH_DAEMON_RESTART = 'service laravel-valetd stop && service laravel-valetd start';
    const LAUNCH_DAEMON_STOP = 'service laravel-valetd stop';
    const LAUNCH_DAEMON_UNLINK = '/etc/init/laravel-valetd';
}

