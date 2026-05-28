#!/bin/bash
cd /var/www
php artisan config:cache
php artisan route:cache
php artisan migrate --force
php-fpm &
nginx -g "daemon off;"