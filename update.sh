#!/usr/bin/env bash

# Remove existing Valet directory
rm -rf $HOME/.valet-cli

# Download and unpack the latest Valet release
mkdir $HOME/.valet-cli
wget https://github.com/laravel/valet/archive/master.tar.gz -O $HOME/.valet-cli/valet.tar.gz
tar xvzf $HOME/.valet-cli/valet.tar.gz -C $HOME/.valet-cli --strip 1 > /dev/null 2>&1

# Install Valet's Composer dependencies
/usr/local/bin/php /usr/local/bin/composer install -d $HOME/.valet-cli

# Run the Valet installation process
$HOME/.valet-cli/valet --version
