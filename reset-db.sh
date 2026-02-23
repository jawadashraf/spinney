#!/bin/bash

# Reset Database Script
# Fresh migrate, seed, shield setup, and assign super admin

set -e

echo "🔄 Fresh migrating database..."
php artisan migrate:fresh --no-interaction

echo "🌱 Running database seeders..."
php artisan db:seed --no-interaction

# echo "🛡️ Running Shield seeder..."
php artisan db:seed --class=ShieldSeeder --no-interaction

echo "🔑 Generating Shield permissions for app panel..."
php artisan shield:generate --all --panel=app --no-interaction

echo "👑 Assigning super_admin role to user ID 1..."
php artisan shield:super-admin --user=1 --panel=app --no-interaction

echo ""
echo "✅ Database reset complete!"
