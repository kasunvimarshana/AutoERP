#!/bin/sh
set -e

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Run migrations on container start (safe for microservices; remove if using separate deploy step)
php artisan migrate --force --no-interaction

# Build runtime caches now that env vars are available
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
