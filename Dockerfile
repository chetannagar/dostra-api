# Development-only Dockerfile for the Laravel API.
#
# This image is intentionally optimized for local development:
# - uses SQLite
# - keeps dev Composer dependencies
# - runs `php artisan serve` instead of Apache
# - auto-creates the SQLite file if it does not exist
# - runs migrations on startup for convenience
#
# For production, use a separate Dockerfile or deployment pipeline.

FROM php:8.4-cli

# Application path inside the container.
WORKDIR /var/www/html

# Install system packages and PHP extensions required by this Laravel app.
# - git/unzip: needed by Composer packages
# - libsqlite3-dev: required for pdo_sqlite
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libsqlite3-dev \
    && docker-php-ext-install \
        pdo \
        pdo_sqlite \
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

# Prepare Laravel writable directories and the local SQLite database file.
RUN mkdir -p \
        database \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && touch database/database.sqlite \
    && chmod -R ug+rwx storage bootstrap/cache database

# Laravel's development server will listen on port 8000.
EXPOSE 8000

# Start the app in development mode.
# - ensure the SQLite file exists
# - run migrations automatically for local convenience
# - serve the app on all interfaces so Docker port mapping works
CMD sh -c "touch database/database.sqlite && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"
