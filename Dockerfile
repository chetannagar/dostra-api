# Development-only Dockerfile for the Laravel API.
#
# This image is intentionally optimized for local development:
# - uses PostgreSQL
# - keeps dev Composer dependencies
# - runs `php artisan serve` instead of Apache
# - runs migrations on startup for convenience
#
# For production, use a separate Dockerfile or deployment pipeline.

# Pin PHP to an exact patched release instead of the floating "8.4" tag,
# which could silently reuse an old cached image.
# Keep in sync with PHP_VERSION in Dockerfile.prod so dev matches production.
# Must satisfy "php" in composer.json (~8.4.27 || ^8.5.12).
ARG PHP_VERSION=8.5.12
FROM php:${PHP_VERSION}-cli

# Application path inside the container.
WORKDIR /var/www/html

# Install system packages and the PHP extensions this Laravel + Filament app
# needs that are NOT already in the official image.
# - git/unzip: needed by Composer packages
# - libicu-dev: required for intl
# - libpq-dev: required for pdo_pgsql
# - libzip-dev: required for zip
#
# Already built into the official php image, so NOT listed here:
# dom, xml, mbstring, pdo, opcache. Recompiling them fails on PHP 8.5.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-install \
        bcmath \
        exif \
        intl \
        pcntl \
        pdo_pgsql \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Copy Composer from the official Composer image so we do not need to install it manually.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy the application source into the container.
COPY . .

# Install Composer dependencies including dev packages because this image is for development.
RUN composer install \
    --no-interaction \
    --no-progress \
    --prefer-dist

# Prepare Laravel writable directories.
RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

# Laravel's development server will listen on port 8000.
EXPOSE 8000

# Start the app in development mode.
# - composer install first: the vendor directory is a named volume that persists
#   across rebuilds, so it can be stale after composer.lock changes
# - run migrations automatically for local convenience
# - serve the app on all interfaces so Docker port mapping works
CMD ["sh", "-c", "composer install --no-interaction --no-progress && php artisan config:cache && php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=8000"]
