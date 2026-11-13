#!/usr/bin/env bash
#
# Production deploy for Neon Pong. Run ON the server, from the app root:
#
#   ./deploy.sh            # pull + deps + caches
#
# Neon Pong has no database, so there is nothing to migrate. Vite assets are
# built LOCALLY and committed in public_html/build — the server never runs npm.
#
# Counterpart: cx.sh (LOCAL).

set -euo pipefail
cd "$(dirname "$0")"

echo "→ git pull"
LOCK_BEFORE=$(git rev-parse HEAD:composer.lock 2>/dev/null || echo none)
git pull --ff-only
LOCK_AFTER=$(git rev-parse HEAD:composer.lock 2>/dev/null || echo none)

if [ "$LOCK_BEFORE" != "$LOCK_AFTER" ] || [ ! -d vendor ]; then
    if command -v composer >/dev/null 2>&1; then
        echo "→ composer install"
        composer install --no-dev --optimize-autoloader --no-interaction
    else
        echo "⚠ composer.lock changed but composer is not on PATH — install dependencies manually."
    fi
fi

# Assets are built locally and committed (public_html/build), so the pull
# above already brought them. Nothing to build here — just check.
if [ ! -f public_html/build/manifest.json ]; then
    echo "⚠ public_html/build/manifest.json missing — run 'npm run build' locally, commit, push."
fi

# The leaderboard lives in storage/app; make sure storage is writable.
mkdir -p storage/app storage/framework/{cache,sessions,views} storage/logs
chmod -R ug+rw storage bootstrap/cache 2>/dev/null || true

echo "→ cache config/routes/views"
php artisan optimize

echo "✓ deployed $(git log --oneline -1)"
