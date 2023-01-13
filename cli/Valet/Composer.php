<?php

namespace Valet;

use DomainException;

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

        return !empty($details);
    }

    public function installOrFail(string $namespacedPackage): void
    {
        info('['.$namespacedPackage.'] is not installed, installing it now via Composer...</info> 🎼');

        $this->cli->runAsUser(('composer global require '.$namespacedPackage), function ($exitCode, $errorOutput) use ($namespacedPackage) {
            output($errorOutput);

            throw new DomainException('Composer was unable to install ['.$namespacedPackage.'].');
        });
    }
}
