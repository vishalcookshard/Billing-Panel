#!/usr/bin/env bash
set -euo pipefail

echo "Billing Panel one-command installer"

usage() {
  echo "Usage: $0"
  echo "Menu will be shown."
  exit 1
}

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker is required. Please install Docker and Docker Compose (or docker-compose)."
  exit 1
fi

while true; do
  echo "\nSelect an option:"
  echo "  1) Install"
  echo "  2) Uninstall"
  echo "  3) Exit"
  read -rp "Choice: " choice

  case "$choice" in
    1)
      read -rp "Enter the FQDN for the app (e.g. billing.example.com): " fqdn
      if [[ -z "$fqdn" ]]; then
        echo "FQDN is required."; continue
      fi

      # Prevent accidental reinstall on an existing system
      if [[ -f .env ]]; then
        echo "An .env exists in this directory. This may be a live installation."
        read -rp "Proceed with install and potentially overwrite existing installation? (yes/no): " confirm
        if [[ "$confirm" != "yes" ]]; then
          echo "Aborting installation to avoid accidental overwrite."; continue
        fi
      fi

      echo "Preparing .env..."
      if [[ -f .env ]]; then
        echo ".env already exists, backing up to .env.bak"
        cp .env .env.bak
      fi

      # Minimal env with FQDN
      cat > .env <<EOF
APP_NAME=BillingPanel
APP_ENV=production
APP_KEY=
APP_URL=https://$fqdn
APP_HOST=$fqdn
LOG_CHANNEL=stack

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=billing
DB_USERNAME=root
DB_PASSWORD=secret

CACHE_DRIVER=file
QUEUE_CONNECTION=redis
SESSION_DRIVER=file

REDIS_HOST=redis
REDIS_PORT=6379

MAIL_MAILER=log

EOF

      echo "Building images (this may take a few minutes)..."
      docker compose build --pull --no-cache

      echo "Starting containers..."
      docker compose up -d

      echo "Waiting for app to be healthy..."
      # wait for health endpoint
      attempt=0
      until docker compose exec -T app php artisan route:list >/dev/null 2>&1 || [ $attempt -ge 30 ]; do
        attempt=$((attempt+1))
        echo "Waiting for app to boot ($attempt/30)..."
        sleep 2
      done

      echo "Generating APP_KEY and running migrations..."
      docker compose exec -T app php artisan key:generate --ansi
      docker compose exec -T app php artisan migrate --force

      # check if an admin already exists to avoid accidental overwrite
      has_admin=$(docker compose exec -T app php -r "require 'vendor/autoload.php'; \$app=require 'bootstrap/app.php'; \$kernel=\$app->make(Illuminate\\Contracts\\Console\\Kernel::class); echo (bool)\App\\Models\\User::where('is_admin',1)->count();") || true
      if [[ "$has_admin" == "1" || "$has_admin" == "true" ]]; then
        echo "Warning: An admin user already exists in the database. Skipping admin creation.";
      else
        echo "Creating admin user..."
        ADMIN_EMAIL=admin@$fqdn
        ADMIN_PASSWORD=$(openssl rand -base64 12)
        docker compose exec -T app php artisan app:install --email=${ADMIN_EMAIL} --password=${ADMIN_PASSWORD}

        echo "Admin user created: ${ADMIN_EMAIL}"
        echo "Admin password: ${ADMIN_PASSWORD}"
      fi

      echo "Starting queue and scheduler..."
      docker compose up -d worker scheduler

      echo "Waiting for worker and scheduler to be running..."
      attempt=0
      until docker compose ps --status running | grep -E "billing-panel-worker|billing-panel-scheduler" >/dev/null 2>&1 || [ $attempt -ge 20 ]; do
        attempt=$((attempt+1))
        echo "Waiting for background services to run ($attempt/20)..."
        sleep 2
      done

      echo "Installation complete. Visit https://$fqdn to access the app."
      ;;
    2)
      echo "Preparing to uninstall. A database backup will be taken automatically."

      # Create backup dir
      mkdir -p backups
      BACKUP_FILE="backups/db-backup-$(date +%Y%m%d-%H%M%S).sql"

      echo "Taking database dump to ${BACKUP_FILE}..."
      if docker compose exec -T db sh -c 'exec mysqldump -u"${MYSQL_USER:-root}" -p"${MYSQL_ROOT_PASSWORD:-${DB_PASSWORD:-secret}}" "${MYSQL_DATABASE:-billing}"' > "${BACKUP_FILE}" 2>/dev/null; then
        echo "Database backup saved to ${BACKUP_FILE}"
      else
        echo "Database backup failed. Proceeding with uninstall only after confirmation."
        read -rp "Continue with uninstall despite failed backup? (yes/no): " confirm
        if [[ "$confirm" != "yes" ]]; then
          echo "Aborting uninstall to avoid data loss."; continue
        fi
      fi

      read -rp "Are you sure you want to uninstall and remove volumes? This will destroy data. (yes/no): " confirm_uninstall
      if [[ "$confirm_uninstall" != "yes" ]]; then
        echo "Uninstall aborted."; continue
      fi

      echo "Stopping and removing containers and volumes..."
      docker compose down -v --remove-orphans
      echo "Uninstall complete. Backup: ${BACKUP_FILE}"
      ;;
    3)
      echo "Exiting."; exit 0;
      ;;
    *) echo "Invalid option";;
  esac

done
