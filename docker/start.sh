#!/bin/sh
set -e

# Clear caches on every deploy
php artisan optimize:clear

# Run pending migrations (--force is required in production)
php artisan migrate --force

# Make sure PHP-FPM can write to these folders after the commands above
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

php-fpm -D
exec nginx -g "daemon off;"