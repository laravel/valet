#!/usr/bin/env bash

# Determine if the port config key exists, if not, create it
CONFIG="$HOME/.valet/config.json"
PORT=$(jq -r ".port" "$CONFIG")

if [[ "$PORT" = "null" ]]
then
    CONTENTS=$(jq '. + {port: "80"}' "$CONFIG")
    echo -n $CONTENTS >| "$CONFIG"
    exit
fi

# Clean NetworkManager cruft
NM="/etc/NetworkManager"
TMP="/tmp/nm.conf"

sudo rm "$NM"/dnsmasq.d/valet
sudo rm "$NM"/conf.d/valet.conf
sudo grep -v "dns=dnsmasq" "$NM/NetworkManager.conf" > "$TMP" && sudo mv "$TMP" "$NM/NetworkManager.conf"
echo "Deleted cruft"
