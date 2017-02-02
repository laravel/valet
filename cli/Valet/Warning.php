<?php

namespace Valet;

class Warning
{
    var $cli;

    /**
     * Create a new Warning instance.
     *
     * @param CommandLine $cli
     */
    public function __construct(CommandLine $cli)
    {
        $this->cli = $cli;
    }

    /**
     * Run all checks and output warnings.
     */
    function check()
    {
        $this->homePathIsInsideRoot();
        $this->seLinuxIsEnabled();
    }

    /**
     * Verify if valet home is inside /root directory.
     *
     * This usually means the HOME parameters has not been
     * kept using sudo.
     */
    function homePathIsInsideRoot()
    {
        if (strpos(VALET_HOME_PATH, '/root/') === 0) {
            warning("⚠️️ Valet home directory is inside /root!");
        }
    }

    /**
     * Verify is SELinux is enabled and in enforcing mode.
     */
    function seLinuxIsEnabled()
    {
        $output = $this->cli->run('sestatus');

        if (preg_match('@SELinux status:(\s+)enabled@', $output)
            && preg_match('@Current mode:(\s+)enforcing@', $output)
        ) {
            warning("⚠️️ SELinux is enabled!");
        }
    }
}
