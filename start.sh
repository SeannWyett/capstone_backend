#!/bin/sh

php artisan migrate --seed --force
php artisan storage:link
php artisan 

php artisan serve --host=0.0.0.0 --port=${PORT:-8000}