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

      echo "Creating admin user..."
      ADMIN_EMAIL=admin@$fqdn
      ADMIN_PASSWORD=$(openssl rand -base64 12)
      docker compose exec -T app php artisan app:install --email=${ADMIN_EMAIL} --password=${ADMIN_PASSWORD}

      echo "Admin user created: ${ADMIN_EMAIL}"
      echo "Admin password: ${ADMIN_PASSWORD}"

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
      echo "Stopping and removing containers and volumes..."
      docker compose down -v --remove-orphans
      echo "Uninstall complete. Backups (if any) are preserved in .env.bak"
      ;;
    3)
      echo "Exiting."; exit 0;
      ;;
    *) echo "Invalid option";;
  esac

done
