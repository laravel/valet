#!/usr/bin/env bash

# Install Homebrew for dependency management
if [[ ! $(which brew -A) ]]
then
    /usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
fi

brew install wget > /dev/null 2>&1

# Install PHP 7.0
brew tap homebrew/dupes
brew tap homebrew/versions
brew tap homebrew/homebrew-php

brew unlink php56 > /dev/null 2>&1
brew install php70

# Install Composer to /usr/local/bin
if [[ ! $(which composer -A) ]]
then
    echo "Installing Composer..."
    /usr/local/bin/php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    /usr/local/bin/php -r "if (hash_file('SHA384', 'composer-setup.php') === '92102166af5abdb03f49ce52a40591073a7b859a86e8ff13338cf7db58a19f7844fbc0bb79b2773bf30791e935dbd938') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" > /dev/null 2>&1
    /usr/local/bin/php composer-setup.php > /dev/null 2>&1
    /usr/local/bin/php -r "unlink('composer-setup.php');"

    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi

COMPOSER_PATH=$(which composer)

# Download and unpack the latest Valet release
rm -rf $HOME/.valet-cli
mkdir $HOME/.valet-cli

echo "Downloading Valet..."
wget https://github.com/laravel/valet/archive/master.tar.gz -O $HOME/.valet-cli/valet.tar.gz > /dev/null 2>&1
tar xvzf $HOME/.valet-cli/valet.tar.gz -C $HOME/.valet-cli --strip 1 > /dev/null 2>&1

rm /usr/local/bin/valet
ln -s $HOME/.valet-cli/valet /usr/local/bin/valet
chmod +x /usr/local/bin/valet

# Install Valet's Composer dependencies
echo "Installing Valet's Composer dependencies..."
/usr/local/bin/php $COMPOSER_PATH install -d $HOME/.valet-cli > /dev/null 2>&1

# Run the Valet installation process
$HOME/.valet-cli/valet install
