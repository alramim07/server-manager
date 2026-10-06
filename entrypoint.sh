#!/bin/sh
set -e

touch database/database.sqlite 2>/dev/null || true

php artisan config:cache
php artisan view:cache
php artisan migrate --force
php artisan db:seed --force

( while true; do
    sleep 60
    php artisan demo:reset-if-idle || true
  done ) &

exec apache2-foreground
