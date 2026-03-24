#!/bin/sh
set -e

cd /var/www/html

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Wait for PostgreSQL to be ready
echo "Waiting for PostgreSQL..."
until php -r "new PDO('pgsql:host=${DB_HOST:-postgres};port=${DB_PORT:-5432};dbname=${DB_DATABASE:-military_task_db}', '${DB_USERNAME:-MTML}', '${DB_PASSWORD:-password}');" 2>/dev/null; do
    sleep 1
done
echo "PostgreSQL is ready."

# Run migrations
php artisan migrate --force

# Clear and cache config
php artisan config:cache
php artisan route:cache

# Start all services via supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
