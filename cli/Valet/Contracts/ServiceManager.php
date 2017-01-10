<?php

namespace Valet\Contracts;

interface ServiceManager
{
    /**
     * Start the given services.
     *
     * @param
     * @return void
     */
    function start($services);

    /**
     * Stop the given services.
     *
     * @param
     * @return void
     */
    function stop($services);

    /**
     * Restart the given services.
     *
     * @param
     * @return void
     */
    function restart($services);

    /**
     * Determine if service manager is available on the system.
     *
     * @return bool
     */
    function isAvailable();
}
