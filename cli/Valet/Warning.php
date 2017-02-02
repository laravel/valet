<?php

namespace Valet;

class Warning
{
    /**
     * Run all checks and output warnings.
     */
    function check()
    {
        $this->homePathIsInsideRoot();
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
}
