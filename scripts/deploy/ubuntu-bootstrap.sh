#!/bin/bash
# One-time bootstrap on Ubuntu 22.04+ (empty VPS). Run as root.
# Usage: bash ubuntu-bootstrap.sh
# After this: install post-receive hook, create .env on server, add nginx site, then git push.
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y git nginx mysql-server software-properties-common curl unzip

add-apt-repository -y ppa:ondrej/php
apt-get update -y
apt-get install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring php8.2-xml \
  php8.2-curl php8.2-zip php8.2-bcmath php8.2-intl

curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

mkdir -p /var/repo /var/www/bookven
chown -R www-data:www-data /var/www/bookven

if [[ ! -d /var/repo/bookven.git ]]; then
  git init --bare /var/repo/bookven.git
fi

echo "Bare repo ready at /var/repo/bookven.git"
echo "Next: copy scripts/deploy/post-receive to /var/repo/bookven.git/hooks/post-receive, chmod +x,"
echo "      create /var/www/bookven/.env, enable nginx (see scripts/deploy/nginx-bookven.conf.example),"
echo "      then from your laptop: git remote add production root@HOST:/var/repo/bookven.git && git push production main"
