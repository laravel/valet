#!/usr/bin/env bash

# Install Homebrew for dependency management
/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"

brew install wget > /dev/null 2>&1

# Install PHP 7.0
brew tap homebrew/dupes
brew tap homebrew/versions
brew tap homebrew/homebrew-php

brew unlink php56 > /dev/null 2>&1
brew install php70

# Install Composer to /usr/local/bin
/usr/local/bin/php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
/usr/local/bin/php -r "if (hash_file('SHA384', 'composer-setup.php') === '92102166af5abdb03f49ce52a40591073a7b859a86e8ff13338cf7db58a19f7844fbc0bb79b2773bf30791e935dbd938') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
/usr/local/bin/php composer-setup.php
/usr/local/bin/php -r "unlink('composer-setup.php');"

mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Download and unpack the latest Valet release
rm -rf $HOME/.valet-cli
mkdir $HOME/.valet-cli
wget https://github.com/laravel/valet/archive/v1.1.3.tar.gz -O $HOME/.valet-cli/valet.tar.gz
tar xvzf $HOME/.valet-cli/valet.tar.gz -C $HOME/.valet-cli --strip 1 > /dev/null 2>&1

ln -s $HOME/.valet-cli/valet /usr/local/bin/valet
chmod +x /usr/local/bin/valet

# Install Valet's Composer dependencies
/usr/local/bin/php composer install -d $HOME/.valet-cli

# Run the Valet installation process
$HOME/.valet-cli/valet install
