#!/usr/bin/env bash
# Deploys the checked-out workspace to a fixed directory on this server and (re)starts only the
# "mamoni" Compose project. It never touches other containers, networks or volumes on the host.
set -euo pipefail

APP_DIR="${APP_DIR:-$HOME/mamoni}"
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE=(docker compose -p mamoni -f compose.yaml -f compose.vps.yaml)

if [ ! -f "$APP_DIR/.env" ]; then
  echo "Missing $APP_DIR/.env. Copy deploy/env.vps.example there and fill it in first." >&2
  exit 1
fi

echo "==> Syncing code to $APP_DIR"
mkdir -p "$APP_DIR" "$APP_DIR/storage/app/public" "$APP_DIR/storage/framework/cache" "$APP_DIR/storage/framework/sessions" "$APP_DIR/storage/framework/views" "$APP_DIR/storage/logs" "$APP_DIR/bootstrap/cache"
# The .gitignore filter also protects generated, root-owned files (vendor, Filament assets, build output) from --delete.
rsync -a --delete --filter=':- .gitignore' \
  --exclude='.git/' --exclude='.github/' --exclude='.env' \
  --exclude='storage/' --exclude='bootstrap/cache/' \
  --exclude='vendor/' --exclude='node_modules/' \
  --exclude='public/build/' --exclude='public/storage' \
  "$SRC"/ "$APP_DIR"/

cd "$APP_DIR"

echo "==> Starting data services and the app image"
"${COMPOSE[@]}" up -d --build postgres redis mailpit app web

echo "==> Fixing storage permissions"
"${COMPOSE[@]}" exec -T -u root app sh -c 'chown -R www-data:www-data storage bootstrap/cache && chmod -R ug+rwX storage bootstrap/cache'

echo "==> Installing PHP dependencies"
"${COMPOSE[@]}" exec -T app composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

if ! grep -q '^APP_KEY=.\+' .env; then
  echo "==> Generating APP_KEY (first deploy only)"
  "${COMPOSE[@]}" exec -T app php artisan key:generate --force
fi

echo "==> Building frontend assets"
"${COMPOSE[@]}" run --rm assets

echo "==> Migrating and caching"
"${COMPOSE[@]}" exec -T app php artisan migrate --force
"${COMPOSE[@]}" exec -T app php artisan storage:link --force
"${COMPOSE[@]}" exec -T app php artisan config:cache
"${COMPOSE[@]}" exec -T app php artisan route:cache
"${COMPOSE[@]}" exec -T app php artisan view:cache

echo "==> Restarting workers"
"${COMPOSE[@]}" up -d worker scheduler
"${COMPOSE[@]}" exec -T app php artisan queue:restart

echo "==> Health check"
PORT="$(grep -E '^WEB_PORT=' .env | cut -d= -f2 || true)"
for attempt in 1 2 3 4 5 6; do
  if curl -fsS -o /dev/null "http://127.0.0.1:${PORT:-8090}/up"; then
    echo "Deploy OK"
    exit 0
  fi
  sleep 5
done
echo "Health check failed; see: docker compose -p mamoni logs --tail=100 app web" >&2
exit 1
