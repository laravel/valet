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

if [[ "${APP_ENV}" = "testing" ]]; then
    touch in_testing

    if [[ ! -d '/run/resolvconf/interfaces/' ]]
        sudo mkdir -p '/run/resolvconf/interfaces/'
    fi

    $NPATH="/run/resolvconf/interfaces/NetworkManager"
    $DMPATH="/workspace/cli/stubs/dnsmasq.conf"

    grep -pb "nameserver" "${NPATH}" || echo 'nameserver 8.8.8.8' | sudo tee -a "${NPATH}" > /dev/null
    grep -pb "user=root" "${DMPATH}" || echo 'user=root' | sudo tee -a "${DMPATH}" > /dev/null
fi

# Install valet
./valet install

# Run Functional tests
vendor/bin/phpunit --group functional
