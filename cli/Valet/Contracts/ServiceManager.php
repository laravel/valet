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
    public function start($services);

    /**
     * Stop the given services.
     *
     * @param
     * @return void
     */
    public function stop($services);

    /**
     * Restart the given services.
     *
     * @param
     * @return void
     */
    public function restart($services);

    /**
     * Status the given services.
     *
     * @param
     * @return void
     */
    public function status($services);

    /**
     * Determine if service manager is available on the system.
     *
     * @return bool
     */
    public function isAvailable();
}
