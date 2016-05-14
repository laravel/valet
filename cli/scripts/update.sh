#!/usr/bin/env bash

# Determine if this is the latest Valet release
LATEST=$($HOME/.valet-cli/valet on-latest-version)

if [[ "$LATEST" = "YES" ]]
then
    echo "You are already using the latest version of Valet."
    exit
fi

# Remove existing Valet directory
rm -rf $HOME/.valet-cli

# Download and unpack the latest Valet release
mkdir $HOME/.valet-cli
echo "Downloading latest release of Valet..."
TARBALL=$(curl -s https://api.github.com/repos/laravel/valet/releases/latest | jq ".tarball_url")
TARBALL=$(echo $TARBALL | sed -e 's/^"//'  -e 's/"$//')
wget --max-redirect=10 $TARBALL -O $HOME/.valet-cli/valet.tar.gz > /dev/null 2>&1
tar xvzf $HOME/.valet-cli/valet.tar.gz -C $HOME/.valet-cli --strip 1 > /dev/null 2>&1

# Install Valet's Composer dependencies
echo "Installing Valet's Composer dependencies..."
/usr/bin/php /usr/local/bin/composer install -d $HOME/.valet-cli > /dev/null 2>&1

# Run the Valet installation process
/usr/local/bin/valet install

# Display the new Valet version
$HOME/.valet-cli/valet --version
