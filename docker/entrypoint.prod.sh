#!/bin/sh
# Production container startup script.
# Runs as www-data (see USER in Dockerfile.prod), so every file created here,
# including caches and logs, is writable by the PHP-FPM workers.
set -e  # stop immediately if any command fails

# Cache config, routes, events, and views using the real runtime env vars.
# Done here rather than at build time so nothing is baked in from the build.
php artisan optimize

# Run migrations unless disabled. Set RUN_MIGRATIONS=false on app containers
# if you run migrations as a separate deploy step instead.
# --isolated takes a cache lock so only one container migrates at a time;
# the lock comes from the cache store: "file" is enough for a single container,
# but multiple replicas need a shared store (redis). Avoid "database" on the
# first deploy: the cache_locks table doesn't exist until migrations have run.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --isolated
fi

# Replace this shell with PHP-FPM so it receives stop signals directly
# and can shut down gracefully.
exec php-fpm