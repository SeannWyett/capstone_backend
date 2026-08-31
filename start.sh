#!/bin/sh

echo "Running migrations and seeding the database..."
php artisan migrate --seed --force

echo "Creating storage symbolic link..."
php artisan storage:link

echo "Starting the development server..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}