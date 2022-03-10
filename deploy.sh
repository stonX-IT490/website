#!/bin/bash

# Update repos
sudo apt update

# Do full upgrade of system
sudo apt full-upgrade -y

# Remove leftover packages and purge configs
sudo apt autoremove -y --purge

# Install required packages
sudo apt install -y php-amqp php-bcmath php-cli php-common php-curl php-fpm php-json php-mbstring php-mysql php-readline php-opcache php-readline nginx

# Install zerotier
sudo apt install -y apt-transport-https ca-certificates curl gnupg lsb-release
curl -s https://install.zerotier.com | sudo bash

# Stop nginx
sudo systemctl stop nginx

# Copy config over
sudo chown -R root:root config/nginx
sudo chmod -R 644 config/nginx
sudo cp -a -r config/nginx/. /etc/nginx/
sudo nginx -t

# Copy website source
sudo rm -rf root /var/www/html/*
sudo cp -r src/. /var/www/html/
sudo chown -R www-data:www-data /var/www/html
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo find /var/www/html -type f -exec chmod 644 {} \;

# Start nginx
sudo systemctl start nginx
