#!/bin/bash

# Terminate as soon as one command fails (e)
set -e

# Source .profile for extra path etc
if [ -f ~/.profile ]
then
    source ~/.profile
fi

# Go into repository workspace
cd $REPOSITORY

# Install valet
./valet install

# Run Functional tests
vendor/bin/phpunit --group functional --exclude-group none
