#!/bin/sh
set -e

touch database/database.sqlite 2>/dev/null || true
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data database storage bootstrap/cache

# Render's generateValue returns raw base64; Laravel requires the base64: prefix.
if [ -n "${APP_KEY:-}" ] && [ "${APP_KEY#base64:}" = "$APP_KEY" ]; then
    if [ "$(printf '%s' "$APP_KEY" | base64 -d 2>/dev/null | wc -c | tr -d ' ')" = "32" ]; then
        export APP_KEY="base64:$APP_KEY"
    fi
fi

php artisan config:cache
php artisan view:cache
php artisan migrate --force
php artisan db:seed --force

( while true; do
    sleep 60
    php artisan demo:reset-if-idle || true
  done ) &

sed -ri -e "s/^Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf
sed -ri -e "s/<VirtualHost \*:80>/<VirtualHost *:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
