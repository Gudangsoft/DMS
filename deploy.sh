#!/bin/bash
# Run this ON THE SERVER (via SSH/aaPanel terminal), from the project root,
# after `git pull` — or let the GitHub Actions workflow run it for you
# automatically on every push to main (see .github/workflows/deploy.yml).
#
# public/build, node_modules, and vendor are all gitignored (standard Laravel
# practice — see .gitignore), so a plain `git pull` never brings the compiled
# Vite assets or dependencies with it. This script rebuilds everything from
# source instead of you having to build locally and upload the result.
set -e

echo "==> Pulling latest code"
git pull origin main

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader

echo "==> Installing Node dependencies & building frontend assets"
npm ci
npm run build

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Rebuilding config/route/view caches"
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Restarting queue workers"
php artisan queue:restart

echo "==> Ensuring storage symlink exists"
php artisan storage:link || true

echo "==> Deploy finished"
