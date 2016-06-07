#!/usr/bin/env bash

TOINSTALL=""

if [[ ! $(which certutil) ]]; then
    TOINSTALL="libnss3-tools"
fi

if [[ ! $(which jq) ]]; then
    TOINSTALL="$TOINSTALL jq"
fi

if [[ ! $(which xsel) ]]; then
    TOINSTALL="$TOINSTALL xsel"
fi

if [[ ! $TOINSTALL = "" ]]; then
    echo "Installing missing system dependencies..."
    sudo apt-get install -y libnss3-tools jq xsel > /dev/null 2>&1 && \
    echo "Dependencies installation completed."
fi