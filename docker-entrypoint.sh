#!/bin/sh
set -eu

cd /app

if [ ! -f .env ]; then
  touch .env
fi

if [ -z "${APP_KEY:-}" ]; then
  php artisan key:generate --force --no-interaction >/dev/null 2>&1 || true
fi

for key in APP_ENV APP_KEY APP_URL DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD DB_SSLMODE DB_EMULATE_PREPARES SUPABASE_URL SUPABASE_KEY; do
  value="$(printenv "$key" 2>/dev/null || true)"
  if [ -n "$value" ]; then
    if grep -q "^${key}=" .env; then
      sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
      printf '%s=%s\n' "$key" "$value" >> .env
    fi
  fi
done

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
