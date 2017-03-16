#!/bin/bash

# Terminate as soon as one command fails (e)
# and log all read inputs to console (v)
set -ev

# Run PHPUnit tests
vendor/bin/phpunit
