#!/usr/bin/env bash
# Robust entrypoint: ensure environment is ready and start php-fpm
set -euo pipefail

log() { echo "[entrypoint] $*"; }
fatal() { echo "[entrypoint][FATAL] $*" >&2; exit 1; }

cd /var/www || fatal "Could not cd to /var/www"


# Prepare directories and set permissions, but never fatally exit for recoverable issues
for dir in storage/logs storage/framework/views storage/framework/cache bootstrap/cache; do
  if ! mkdir -p "$dir"; then
    log "Warning: Could not create $dir (may already exist or permission issue)"
  fi
done

# Set permissions safely. Only chown when running as root, otherwise log warning
if [ "$(id -u)" -eq 0 ]; then
  if ! chown -R app:app storage bootstrap/cache; then
    log "Warning: chown failed for storage/bootstrap/cache (may be bind mount or already correct)"
  fi
else
  # Verify directories are writable by the current user
  for dir in storage bootstrap/cache; do
    if [ ! -w "$dir" ]; then
      log "Warning: Directory $dir is not writable by the container user (uid=$(id -u)). App may fail if permissions are not correct."
    fi
  done
fi
if ! chmod -R 775 storage bootstrap/cache; then
  log "Warning: chmod failed for storage/bootstrap/cache (may be bind mount or already correct)"
fi

# Ensure .env exists
if [ ! -f .env ]; then
  log ".env not found, copying from .env.example"
  cp .env.example .env || fatal "Could not copy .env.example to .env"
fi


# Generate APP_KEY if missing (without booting the framework to avoid provider side effects)
if ! grep -q "^APP_KEY=" .env || grep -q "^APP_KEY=$" .env; then
  log "Generating APP_KEY..."
  php -r 'file_exists(".env") || copy(".env.example", ".env"); $env = file_get_contents(".env"); if (!preg_match("/^APP_KEY=.+/m", $env)) { file_put_contents(".env", rtrim($env, "\n") . "\nAPP_KEY=base64:" . base64_encode(random_bytes(32)) . "\n"); }' || fatal "Failed to generate APP_KEY"
fi


# Validate environment before running migrations
log "Validating environment..."
php artisan env:validate || fatal "Environment validation failed."


# Wait for DB to be ready before running migrations
if [ "${APP_ENV:-}" != "testing" ]; then
  log "Checking database connectivity before migrations..."
  for i in {1..30}; do
    if php artisan migrate:status > /dev/null 2>&1; then
      log "Database is ready. Running migrations..."
      if ! php artisan migrate --force; then
        fatal "Migrations failed. See logs for details. Startup aborted."
      fi
      break
    else
      log "Waiting for database... ($i/30)"
      sleep 2
    fi
    if [ "$i" -eq 30 ]; then
      fatal "Database not ready after 60 seconds. Startup aborted."
    fi
  done
fi

log "Startup complete, launching php-fpm in foreground"
exec php-fpm -F
