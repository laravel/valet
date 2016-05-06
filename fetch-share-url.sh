#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PBCOPY=$(which pbcopy)

if [ -z "$PBCOPY" ]; then
    PBCOPY='xsel --clipboard --input'
fi

php $DIR/valet.php fetch-share-url | $PBCOPY
