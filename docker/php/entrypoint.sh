#!/bin/sh
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

if [ -z "$APP_KEY" ] && ! grep -q "^APP_KEY=base64:" .env; then
    php artisan key:generate --force
fi

if [ "${AUTO_MIGRATE:-true}" = "true" ]; then
    php artisan migrate --force
fi

if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

chown -R www-data:www-data storage bootstrap/cache database

exec "$@"
