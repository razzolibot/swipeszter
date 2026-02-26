#!/bin/sh
set -e

echo "🚀 Swipeszter indítás..."

# Storage symlink
php artisan storage:link --force 2>/dev/null || true

# Cache clear + újragenerálás (fly.io-n az env vars runtime-ban jönnek)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migráció (--force production módban is lefut)
echo "🗄️ Migrációk futtatása..."
php artisan migrate --force

echo "✅ Kész, supervisor indul..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
