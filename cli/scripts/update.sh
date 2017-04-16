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
