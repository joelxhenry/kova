#!/bin/sh
set -e

# Set storage permissions
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
until php artisan db:monitor --databases=mysql > /dev/null 2>&1; do
    sleep 1
done
echo "MySQL is ready."

# Generate app key if missing
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    php artisan key:generate --force
fi

# Run migrations
php artisan migrate --force

# Execute the CMD passed to the container
exec "$@"
