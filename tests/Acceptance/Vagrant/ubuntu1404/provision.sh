#!/bin/bash
set -e

sudo apt-get update

# Install OS Requirements
sudo apt-get install -y network-manager libnss3-tools jq xsel

# Add PPAs
sudo add-apt-repository -y ppa:nginx/stable
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update

# Install Nginx & PHP
sudo apt-get install -y --force-yes nginx curl zip unzip git \
              php5.6-fpm php5.6-cli php5.6-mcrypt php5.6-mbstring php5.6-xml php5.6-curl

# Install Composer
php -r "readfile('http://getcomposer.org/installer');" | sudo php -- --install-dir=/usr/bin/ --filename=composer

# Remove .composer directory created during installation
sudo rm -rf ~/.composer

# Configure Composer
mkdir -p ~/.config/composer
if [ "$VALET_ENVIRONMENT" == "testing" ]
then
  # If we are testing, we mirror the repository
  # so the shared folder stays untouched
  echo '{
    "minimum-stability": "dev",
    "repositories": [
      {
        "type": "path",
        "url": "/home/vagrant/cpriego-valet-linux",
        "options": {
          "symlink": false
        }
      }
    ]
  }' >> ~/.config/composer/composer.json
else
  # If we are developing, we sync the repository with the shared folder
  echo '{
    "minimum-stability": "dev",
    "repositories": [
      {
        "type": "path",
        "url": "/home/vagrant/cpriego-valet-linux"
      }
    ]
  }' >> ~/.config/composer/composer.json
fi

# Require Valet
composer global require "cpriego/valet-linux @dev" --no-interaction --no-ansi

# Add Composer bin to PATH
echo "PATH=\"\$HOME/.config/composer/vendor/bin:\$PATH\"" >> ~/.profile
source ~/.profile
