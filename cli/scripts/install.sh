#!/usr/bin/env bash

apt-get install libnss3-tools jq xsel > /dev/null 2>&1

# Install PHP 7.0
if [[ ! $(apt-cache search php[5-7].[0-9]-cli) ]]
then
    add-apt-repository -y ppa:ondrej/php && apt-get update
fi

$VERSION='7.0'
# Install PHP $VERSION
apt-get install -y "php$VERSION-cli php$VERSION-common php$VERSION-curl php$VERSION-json php$VERSION-mbstring php$VERSION-mcrypt php$VERSION-mysql php$VERSION-opcache php$VERSION-readline php$VERSION-xml php$VERSION-zip"

# Install Composer to /usr/local/bin
if [[ ! $(which -a composer) ]]
then
    echo "Installing Composer..."
    /usr/bin/php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    /usr/bin/php -r "if (hash_file('SHA384', 'composer-setup.php') === '92102166af5abdb03f49ce52a40591073a7b859a86e8ff13338cf7db58a19f7844fbc0bb79b2773bf30791e935dbd938') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" > /dev/null 2>&1
    /usr/bin/php composer-setup.php > /dev/null 2>&1
    /usr/bin/php -r "unlink('composer-setup.php');"

    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi

COMPOSER_PATH=$(which composer)

# Download and unpack the latest Valet release
rm -rf $HOME/.valet-cli
mkdir $HOME/.valet-cli

echo "Downloading Valet..."
TARBALL=$(curl -s https://api.github.com/repos/cpriego/valet-ubuntu/releases/latest | jq ".tarball_url")
TARBALL=$(echo $TARBALL | sed -e 's/^"//'  -e 's/"$//')
wget --max-redirect=10 $TARBALL -O $HOME/.valet-cli/valet.tar.gz > /dev/null 2>&1
tar xvzf $HOME/.valet-cli/valet.tar.gz -C $HOME/.valet-cli --strip 1 > /dev/null 2>&1

# Install Valet to /usr/local/bin
ln -snf $HOME/.valet-cli/valet /usr/local/bin/valet
chmod +x /usr/local/bin/valet

# Install Valet's Composer dependencies
echo "Installing Valet's Composer dependencies..."
/usr/bin/php $COMPOSER_PATH install -d $HOME/.valet-cli > /dev/null 2>&1

# Run the Valet server installation process
/usr/local/bin/valet install
