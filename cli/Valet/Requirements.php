<?php

namespace Valet;

use RuntimeException;

class Requirements
{
    public $cli;
    public $ignoreSELinux = false;

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
     * Determine if SELinux check should be skipped
     *
     * @param bool $ignore
     * @return $this
     */
    public function setIgnoreSELinux($ignore = true)
    {
        $this->ignoreSELinux = $ignore;

        return $this;
    }

    /**
     * Run all checks and output warnings.
     */
    public function check()
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
    public function homePathIsInsideRoot()
    {
        if (strpos(VALET_HOME_PATH, '/root/') === 0) {
            throw new RuntimeException("Valet home directory is inside /root");
        }
    }

    /**
     * Verify is SELinux is enabled and in enforcing mode.
     */
    public function seLinuxIsEnabled()
    {
        if ($this->ignoreSELinux) {
            return;
        }

        $output = $this->cli->run('sestatus');

        if (preg_match('@SELinux status:(\s+)enabled@', $output)
            && preg_match('@Current mode:(\s+)enforcing@', $output)
        ) {
            throw new RuntimeException("SELinux is in enforcing mode");
        }
    }
}
