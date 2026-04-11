#!/bin/bash

# Reset Database Script
# Fresh migrate, seed, shield setup, and assign super admin

set -e

echo "🔄 Fresh migrating database..."
php artisan migrate:fresh --no-interaction

# The standard db:seed calls LocalSeeder, which already triggers ShieldSeeder.
php artisan db:seed --no-interaction

echo "🔑 Generating Shield permissions for app panel..."
# php artisan shield:generate --all --panel=app --no-interaction --relationships
php artisan shield:generate --all --panel=app --no-interaction

php artisan db:seed --class=SimplifiedRolePermissionSeeder --no-interaction

echo "👑 Assigning super_admin role to user ID 1..."
php artisan shield:super-admin --user=1 --panel=app --no-interaction --tenant=1

echo ""
echo "✅ Database reset complete!"
