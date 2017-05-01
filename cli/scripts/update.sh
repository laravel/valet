#!/usr/bin/env bash

# Determine if the port config key exists, if not, create it
function fix-config() {
    local CONFIG="$HOME/.valet/config.json"

    if [[ -f $CONFIG ]]
    then
        local PORT=$(jq -r ".port" "$CONFIG")

        if [[ "$PORT" = "null" ]]
        then
            echo "Fixing valet config file..."
            CONTENTS=$(jq '. + {port: "80"}' "$CONFIG")
            echo -n $CONTENTS >| "$CONFIG"
        fi
    fi
}

function cleanup {
    local NM="/etc/NetworkManager"
    local TMP="/tmp/nm.conf"

    if [[ -f "$NM"/dnsmasq.d/valet ]]
    then
        echo "Removing old dnsmasq config file..."
        sudo rm "$NM"/dnsmasq.d/valet
    fi

    if [[ -f "$NM"/conf.d/valet.conf ]]
    then
        echo "Removing old NetworkManager config file..."
        sudo rm "$NM"/conf.d/valet.conf
    fi

    if grep -xq "dns=dnsmasq" "$NM/NetworkManager.conf"
    then
        echo "Removing dnsmasq control from NetworkManager..."
        sudo grep -v "dns=dnsmasq" "$NM/NetworkManager.conf" > "$TMP" && sudo mv "$TMP" "$NM/NetworkManager.conf"
    fi

    echo "Cleanup done."
}

if [[ "$1" = "update" ]]
then
    composer global update "cpriego/valet-linux"
fi

fix-config
# cleanup
