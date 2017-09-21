#!/bin/bash
set -e

# Enable remirepo
sudo yum -y install https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
sudo yum -y install http://rpms.remirepo.net/enterprise/remi-release-7.rpm
sudo yum -y install yum-utils
sudo yum-config-manager --enable remi-php56

# Install OS Requirements
sudo yum install -y nss-tools jq xsel

# Install Nginx & PHP
sudo yum install -y nginx curl zip unzip git \
              php-fpm php-cli php-mcrypt php-mbstring php-xml php-curl php-posix

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
echo "PATH=\"\$HOME/.config/composer/vendor/bin:\$PATH\"" >> ~/.bashrc
source ~/.bashrc

# Disable SELinux
sudo setenforce 0
sudo sed -i 's/SELINUX=enforcing/SELINUX=permissive/g' /etc/selinux/config
