#!/usr/bin/env bash
# Robust entrypoint: ensure environment is ready and start php-fpm
set -euo pipefail

log() { echo "[entrypoint] $*"; }
fatal() { echo "[entrypoint][FATAL] $*" >&2; exit 1; }

cd /var/www || fatal "Could not cd to /var/www"

# Prepare directories
for dir in storage/logs storage/framework/views storage/framework/cache bootstrap/cache; do
  mkdir -p "$dir" || log "Could not create $dir (non-fatal)"
done

# Set permissions
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || log "chown failed (non-fatal)"
chmod -R 775 storage bootstrap/cache 2>/dev/null || log "chmod failed (non-fatal)"

# Ensure .env exists
if [ ! -f .env ]; then
  log ".env not found, copying from .env.example"
  cp .env.example .env || fatal "Could not copy .env.example to .env"
fi

# Run composer install (no-scripts to avoid side effects)
if [ ! -f vendor/autoload.php ]; then
  log "Preparing environment for composer..."
  git config --global --add safe.directory /var/www || log "git safe.directory config failed"
  export COMPOSER_ALLOW_SUPERUSER=1
  log "Running composer install..."
  COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --no-scripts --optimize-autoloader || fatal "Composer install failed"
fi

# Generate APP_KEY if missing (without booting the framework to avoid provider side effects)
if ! grep -q "^APP_KEY=" .env || grep -q "^APP_KEY=$" .env; then
  log "Generating APP_KEY..."
  php -r 'file_exists(".env") || copy(".env.example", ".env"); $env = file_get_contents(".env"); if (!preg_match("/^APP_KEY=.+/m", $env)) { file_put_contents(".env", rtrim($env, "\n") . "\nAPP_KEY=base64:" . base64_encode(random_bytes(32)) . "\n"); }' || fatal "Failed to generate APP_KEY"
fi

# Run artisan commands (package:discover removed from composer scripts to avoid binding errors)
if [ "${APP_ENV:-}" != "testing" ]; then
  log "Running migrations..."
  php artisan migrate --force || log "Migrations failed, continuing startup."
fi

log "Startup complete, launching php-fpm in foreground"
exec php-fpm -F
