FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    nginx

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Copy nginx config
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Expose port
EXPOSE 80

COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Start script (runs php-fpm and nginx together)
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh
CMD ["/start.sh"]