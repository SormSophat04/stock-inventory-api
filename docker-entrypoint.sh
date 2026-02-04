#!/bin/bash

# Exit on fail
set -e

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Cache config and routes
# Caching configuration...
# php artisan config:cache
# php artisan route:cache
# php artisan view:cache
# Create storage directories if they don't exist
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/testing
chmod -R 775 storage

# Start Apache in foreground
echo "Starting Apache..."
exec apache2-foreground
