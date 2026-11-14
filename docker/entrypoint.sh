#!/bin/bash
set -e

# Ensure required directories exist
mkdir -p /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    /var/log/supervisor

# Clean any copied host cache files that might reference dev packages
rm -f /var/www/html/bootstrap/cache/*.php

# Discover production packages
php /var/www/html/artisan package:discover --ansi || true

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/log/supervisor
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# If arguments passed, execute them, otherwise run supervisord
if [ "$#" -eq 0 ]; then
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
else
    exec "$@"
fi
