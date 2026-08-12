#!/bin/bash
# Run this ON THE SERVER (via SSH/aaPanel terminal), from the project root,
# after `git pull` — or let the GitHub Actions workflow run it for you
# automatically on every push to main (see .github/workflows/deploy.yml).
#
# public/build is now committed directly to the repo (see .gitignore), so a
# plain `git pull` already brings working compiled assets even on servers
# with no Node.js — the npm rebuild below is a best-effort refresh, not a
# hard requirement, and won't abort the deploy if npm isn't installed.
set -e

# Fetch + hard reset instead of `git pull` — this is a deployment target, not
# a place for local edits, so any drift on the server (file permissions,
# stray manual edits, etc.) should always lose to origin/main rather than
# blocking the deploy with a merge conflict.
echo "==> Syncing to latest origin/main"
git fetch origin main
git reset --hard origin/main

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader

if command -v npm >/dev/null 2>&1; then
    echo "==> Installing Node dependencies & rebuilding frontend assets"
    npm ci
    npm run build
else
    echo "==> npm not found — skipping asset rebuild (using the assets already committed in public/build)"
fi

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Rebuilding config/route/view caches"
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan event:cache
# Non-fatal: fails if APP_DEBUG=true (Laravel's own exception-page views hit
# a framework quirk during caching) — production should have APP_DEBUG=false
# anyway, but this makes sure a stale flag doesn't block the whole deploy.
php artisan view:cache || echo "==> view:cache failed (check APP_DEBUG in .env) — continuing anyway"

echo "==> Restarting queue workers"
php artisan queue:restart

echo "==> Ensuring storage symlink exists"
php artisan storage:link || true

echo "==> Deploy finished"
