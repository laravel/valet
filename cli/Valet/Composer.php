<?php

namespace Valet;

class Composer
{
    public function __construct(public CommandLine $cli)
    {
    }

    public function installed(string $namespacedPackage): bool
    {
        $result = $this->cli->runAsUser("composer global show --format json -- $namespacedPackage");

        if (starts_with($result, 'Changed current')) {
            $result = strstr($result, '{');
        }

        // should be a json response, but if not installed then "not found"
        if (str_contains($result, 'InvalidArgumentException') && str_contains($result, 'not found')) {
            return false;
        }

        $details = json_decode($result, true);

        return ! empty($details);
    }
}
