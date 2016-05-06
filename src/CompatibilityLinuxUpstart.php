<?php

namespace Valet;


class CompatibilityLinuxUpstart extends CompatibilityLinuxSystemd
{
    const LAUNCH_DAEMON_INSTALL_SCRIPT = '/../stubs/linux.upstart';
    const LAUNCH_DAEMON_INSTALL_PATH = '/etc/init.d/laravel-valetd';

    const LAUNCH_DAEMON_QUIETLY_START = 'start laravel-valetd > /dev/null';
    const LAUNCH_DAEMON_QUIETLY_RESTART = '';
    const LAUNCH_DAEMON_RESTART = 'service laravel-valetd stop && service laravel-valetd start';
    const LAUNCH_DAEMON_STOP = 'stop laravel-valetd';
    const LAUNCH_DAEMON_UNLINK = '/etc/init.d/laravel-valetd';
}

