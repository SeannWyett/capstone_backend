#!/bin/sh

# Clear caches on every deploy
php artisan optimize:clear
php artisan cache:clear

# Make sure PHP-FPM can write to these folders after the commands above
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

php-fpm -D
nginx -g "daemon off;"