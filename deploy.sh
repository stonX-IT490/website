#!/bin/bash

read -p "Which cluster? (prod, qa, dev) " cluster

rabbit_ip="broker"
check=$( getent hosts | grep -e broker )

if [ "$check" == "" ]; then
  if [ $cluster == "dev" ]; then
    echo "10.4.90.102 broker" | sudo tee -a /etc/hosts
  fi

  if [ $cluster == "qa" ]; then
    echo "10.4.90.152 broker" | sudo tee -a /etc/hosts
  fi

  if [ $cluster == "prod" ]; then
    echo "10.4.90.52 broker" | sudo tee -a /etc/hosts
    echo "10.4.90.62 broker" | sudo tee -a /etc/hosts
  fi
fi

# Update repos
sudo apt update

# Do full upgrade of system
sudo apt full-upgrade -y

# Remove leftover packages and purge configs
sudo apt autoremove -y --purge

# Install required packages
sudo apt install -y ufw php-amqp php-bcmath php-cli php-common php-curl php-fpm php-json php-mbstring php-mysql php-readline php-opcache php-gmp php-zip nginx wget unzip inotify-tools 

# Setup firewall
sudo ufw --force enable
sudo ufw allow ssh
sudo ufw allow http
sudo ufw allow https
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Install zerotier
sudo apt install -y apt-transport-https ca-certificates curl gnupg lsb-release
curl -s https://install.zerotier.com | sudo bash

# RabbitMQ
cd src/lib/
git clone git@github.com:stonX-IT490/rabbitmq-common.git
cd rabbitmq-common
./deploy.sh
cd ..
git clone git@github.com:stonX-IT490/rabbitmq-common.git rabbitmq-webDmzHost
cd rabbitmq-webDmzHost
./deploy.sh
cd ..

rabbitWebHost="<?php

\$config = [
  'host' =>'$rabbit_ip',
  'port' => 5672,
  'username' => 'webserver',
  'password' => 'stonx_websrv',
  'vhost' => 'webHost'
];

?>"

rabbitWebDmzHost="<?php

\$config = [
  'host' => '$rabbit_ip',
  'port' => 5672,
  'username' => 'webserver',
  'password' => 'stonx_websrv',
  'vhost' => 'webDmzHost'
];

?>"

echo "$rabbitWebHost" > rabbitmq-common/config.php
echo "$rabbitWebDmzHost" > rabbitmq-webDmzHost/config.php
cd ../../

# Stop nginx
sudo systemctl stop nginx

# Setup Self Signed Cert
if [ $cluster != "prod" ]; then
  sudo openssl req -subj '/CN=stonX/OU=IT 490/O=NJIT/C=US' -new -newkey rsa:2048 -sha256 -days 365 -nodes -x509 -keyout /etc/ssl/private/nginx-selfsigned.key -out /etc/ssl/certs/nginx-selfsigned.crt
  sudo openssl dhparam -out /etc/ssl/dhparam.pem 2048
fi

# Copy config over
sudo cp -r config/nginx/. /etc/nginx/
if [ $cluster == "prod" ]; then
  sudo cp -r config/nginx/site.prod.conf /etc/nginx/site.conf
fi
sudo chown -R root:root /etc/nginx
sudo find /etc/nginx -type d -exec chmod 755 {} \;
sudo find /etc/nginx -type f -exec chmod 644 {} \;
sudo nginx -t

# Copy website source
sudo rm -rf root /var/www/html/*
sudo cp -r src/. /var/www/html/
sudo chown -R www-data:www-data /var/www/html
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo find /var/www/html -type f -exec chmod 644 {} \;

# Start nginx
sudo systemctl start nginx

# Setup Central Logging
git clone git@github.com:stonX-IT490/logging.git ~/logging
cd /home/webserver/logging
chmod +x deploy.sh
./deploy.sh
cd /home/webserver/

# Reload systemd
sudo systemctl daemon-reload
