<?php

namespace Valet;

class Expose
{
    public function __construct(public Composer $composer)
    {
    }

    public function currentTunnelUrl(?string $domain = null): string
    {
        return '@todo';
    }

    /**
     * Return whether Expose is installed.
     */
    public function installed(): bool
    {
        return $this->composer->installed('beyondcode/expose');
    }

    /**
     * Make sure Expose is installed.
     */
    public function ensureInstalled(): void
    {
        // @todo: Check if which expose works; if not, prompt:
        // composer global require beyondcode/expose
    }
}
