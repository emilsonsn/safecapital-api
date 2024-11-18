FROM php:8.3-fpm-alpine3.19

# Set working directory
WORKDIR /var/www

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    bash \
    curl \
    libpng-dev \
    libzip-dev \
    zlib-dev \
    mysql-client \
    nodejs \
    npm \
    && docker-php-ext-install pdo_mysql gd zip \
    && rm -rf /var/cache/apk/*

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files to the container
COPY . /var/www

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

# Run Artisan commands for caching (consider this step for local development or pre-production builds)
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 9000
