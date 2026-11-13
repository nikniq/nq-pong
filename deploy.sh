#!/usr/bin/env bash
#
# Production deploy for Neon Pong. Run ON the server, from the app root:
#
#   ./deploy.sh            # pull + deps + assets + caches
#
# Neon Pong has no database, so there is nothing to migrate. What can drift
# is dependencies and the Vite build: public_html/build is gitignored, so a
# pull alone ships Blade views that point at assets the server doesn't have.
#
# Counterpart: cx.sh (LOCAL). If the server has no node/npm, cx.sh option 21
# builds locally and rsyncs public_html/build up instead.

set -euo pipefail
cd "$(dirname "$0")"

echo "→ git pull"
LOCK_BEFORE=$(git rev-parse HEAD:composer.lock 2>/dev/null || echo none)
PKG_BEFORE=$(git rev-parse HEAD:package-lock.json 2>/dev/null || echo none)
ASSETS_BEFORE=$(git rev-parse HEAD:resources 2>/dev/null || echo none)
git pull --ff-only
LOCK_AFTER=$(git rev-parse HEAD:composer.lock 2>/dev/null || echo none)
PKG_AFTER=$(git rev-parse HEAD:package-lock.json 2>/dev/null || echo none)
ASSETS_AFTER=$(git rev-parse HEAD:resources 2>/dev/null || echo none)

if [ "$LOCK_BEFORE" != "$LOCK_AFTER" ] || [ ! -d vendor ]; then
    if command -v composer >/dev/null 2>&1; then
        echo "→ composer install"
        composer install --no-dev --optimize-autoloader --no-interaction
    else
        echo "⚠ composer.lock changed but composer is not on PATH — install dependencies manually."
    fi
fi

if [ "$PKG_BEFORE" != "$PKG_AFTER" ] || [ "$ASSETS_BEFORE" != "$ASSETS_AFTER" ] \
   || [ ! -f public_html/build/manifest.json ]; then
    if command -v npm >/dev/null 2>&1; then
        echo "→ npm ci && npm run build"
        npm ci --no-audit --no-fund
        npm run build
    else
        echo "⚠ Assets changed but npm is not on PATH."
        echo "  Run cx.sh option 21 locally to build and upload public_html/build."
    fi
fi

# The leaderboard lives in storage/app; make sure storage is writable.
mkdir -p storage/app storage/framework/{cache,sessions,views} storage/logs
chmod -R ug+rw storage bootstrap/cache 2>/dev/null || true

echo "→ cache config/routes/views"
php artisan optimize

echo "✓ deployed $(git log --oneline -1)"
