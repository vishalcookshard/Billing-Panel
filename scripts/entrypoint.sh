
#!/usr/bin/env bash
# Robust entrypoint: never exit early, always log errors, keep container alive
set -u

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

# Generate APP_KEY if missing
if ! grep -q "^APP_KEY=" .env || grep -q "^APP_KEY=$" .env; then
  log "Generating APP_KEY..."
  php artisan key:generate --ansi || fatal "Failed to generate APP_KEY"
fi

# Run composer install with --no-scripts
if [ ! -f vendor/autoload.php ]; then
  log "Running composer install..."
  composer install --no-interaction --no-scripts --optimize-autoloader || fatal "Composer install failed"
fi

# Run artisan commands
log "Running artisan package:discover..."
php artisan package:discover --ansi || log "package:discover failed (non-fatal)"
if [ "${APP_ENV:-}" != "testing" ]; then
  log "Running migrations..."
  php artisan migrate --force || log "Migrations failed, continuing startup."
fi

log "Startup complete, launching php-fpm in foreground"
exec php-fpm
