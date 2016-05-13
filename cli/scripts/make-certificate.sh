#!/bin/bash
 
tld=$1
domain=*.$tld
commonname=$domain
 
country=US
state=Arkansas
locality=LittleRock
organization=TopSecretDevelopment
organizationalunit=Zonda
email=zonda@laravel.com
 
if [ -z "$tld" ]
then
    echo "Argument not present."
    echo "Useage $0 [tld]"
 
    exit 99
fi

certs=$HOME/.valet/Certificates
fn=$certs/$1

mkdir $certs
rm -f $fn.key $fn.pem $fn.csr $fn.crt
 
openssl genrsa -out $fn.key 2048 -noout
openssl req -new -key $fn.key -out $fn.csr \
    -subj "/C=$country/ST=$state/L=$locality/O=$organization/OU=$organizationalunit/CN=$commonname/emailAddress=$email"
openssl x509 -req -days 365 -in $fn.csr -signkey $fn.key -out $fn.crt

cat $fn.crt $fn.key | tee $fn.pem