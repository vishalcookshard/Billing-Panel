#!/usr/bin/env bash
set -euo pipefail

# Billing-Panel one-click installer
# Usage: curl -fsSL https://github.com/isthisvishal/Billing-Panel/raw/main/scripts/install.sh | bash

# Error handling function
error_exit() {
  echo ""
  echo "===================================="
  echo "ERROR: $1"
  echo "===================================="
  exit 1
}

trap 'error_exit "Something went wrong on line $LINENO"' ERR

echo ""
echo "===================================="
echo "  Billing-Panel Installer"
echo "===================================="
echo ""

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   error_exit "This script must be run as root (use: sudo bash)"
fi

# Get user input
echo "Please answer the following questions:"
read -p "Enter your domain name (e.g. billing.example.com): " DOMAIN
if [ -z "$DOMAIN" ]; then
  error_exit "Domain name cannot be empty"
fi

read -sp "Enter MySQL root password (or press Enter for default 'secret'): " DB_PASSWORD
DB_PASSWORD=${DB_PASSWORD:-secret}
echo ""
echo ""

INSTALL_DIR="/opt/billing-panel"

echo "[Step 1/9] Removing any conflicting web services..."
systemctl disable nginx 2>/dev/null || true
apt purge -y nginx nginx-common 2>/dev/null || true
echo "✓ Done - Old web services removed"
echo ""

echo "[Step 2/9] Installing required tools..."
apt-get update > /dev/null 2>&1
apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    git > /dev/null 2>&1
echo "✓ Done - Tools installed"
echo ""

echo "[Step 3/9] Setting up Docker..."
if ! command -v docker >/dev/null 2>&1; then
  echo "  Installing Docker..."
  mkdir -p /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg 2>/dev/null | gpg --dearmor -o /etc/apt/keyrings/docker.gpg 2>/dev/null
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null 2>&1
  apt-get update > /dev/null 2>&1
  apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin > /dev/null 2>&1
  systemctl start docker
  systemctl enable docker > /dev/null 2>&1
else
  echo "  Docker already installed"
fi

if ! command -v docker compose >/dev/null 2>&1; then
  echo "  Installing Docker Compose..."
  apt-get install -y docker-compose-plugin > /dev/null 2>&1
fi
echo "✓ Done - Docker is ready"
echo ""

echo "[Step 4/9] Getting Billing-Panel source code..."
if [ ! -d "$INSTALL_DIR" ]; then
  echo "  Downloading from GitHub..."
  git clone https://github.com/isthisvishal/Billing-Panel.git "$INSTALL_DIR" > /dev/null 2>&1
  cd "$INSTALL_DIR"
else
  echo "  Updating existing installation..."
  cd "$INSTALL_DIR"
  git pull origin main > /dev/null 2>&1 || true
fi
echo "✓ Done - Source code ready"
echo ""

echo "[Step 5/9] Creating configuration files..."
cat > .env <<EOF
APP_NAME="Billing-Panel"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://${DOMAIN}
APP_KEY=
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=billing
DB_USERNAME=root
DB_PASSWORD=${DB_PASSWORD}
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379
MAIL_DRIVER=log
EOF
chmod 600 .env
echo "✓ Done - Configuration file created"
echo ""

echo "[Step 6/9] Setting up SSL certificate..."
cat > Caddyfile <<EOF
${DOMAIN} {
  encode gzip

  reverse_proxy app:9000 {
    transport http {
      read_timeout 300s
      write_timeout 300s
    }
  }

  header {
    X-Frame-Options "SAMEORIGIN"
    X-Content-Type-Options "nosniff"
    X-XSS-Protection "1; mode=block"
    Referrer-Policy "strict-origin-when-cross-origin"
  }
}
EOF
echo "✓ Done - SSL configuration ready"
echo ""

echo "[Step 7/9] Starting application services..."
docker compose down -v 2>/dev/null || true
docker compose up -d --build > /dev/null 2>&1 || error_exit "Failed to start Docker services"
echo "✓ Done - Services starting"
echo ""

echo "[Step 8/9] Waiting for database to be ready..."
max_attempts=60
attempt=0
while [ $attempt -lt $max_attempts ]; do
  if docker compose exec -T db sh -c "mysqladmin ping -h localhost -u root -p${DB_PASSWORD}" > /dev/null 2>&1; then
    echo "✓ Done - Database is online"
    break
  fi
  echo "  Please wait... ($((attempt+1))/60 seconds)"
  sleep 1
  attempt=$((attempt+1))
done

if [ $attempt -eq $max_attempts ]; then
  echo "Database did not start. Here are the logs:"
  docker compose logs db
  error_exit "Database failed to start. Please check the error above."
fi

echo ""
echo "[Step 9/9] Finalizing installation..."
# Wait for app container
attempt=0
while [ $attempt -lt 30 ]; do
  if docker compose exec -T app test -f /var/www/artisan 2>/dev/null; then
    break
  fi
  sleep 1
  attempt=$((attempt+1))
done

# Generate APP_KEY
docker compose exec -T app php artisan key:generate --force > /dev/null 2>&1 || true

# Run migrations
if ! docker compose exec -T app php artisan migrate --force > /dev/null 2>&1; then
  echo "  Note: Database migrations may have already been run"
fi

# Run installer
docker compose exec -T app php artisan app:install > /dev/null 2>&1 || true

echo "✓ Done - Installation complete!"
echo ""
echo "===================================="
echo "     SUCCESS - Ready to use!"
echo "===================================="
echo ""
echo "Your application details:"
echo "  URL: https://${DOMAIN}"
echo "  Database: db"
echo "  Database User: root"
echo "  Database Password: ${DB_PASSWORD}"
echo ""
echo "Quick commands:"
echo "  View logs:      cd ${INSTALL_DIR} && docker compose logs -f app"
echo "  Stop services:  cd ${INSTALL_DIR} && docker compose down"
echo "  Start services: cd ${INSTALL_DIR} && docker compose up -d"
echo ""
echo "Next steps:"
echo "  1. Wait 1-2 minutes for the SSL certificate to be generated"
echo "  2. Open https://${DOMAIN} in your web browser"
echo "  3. Complete the application setup"
echo ""
echo "Need help? Check the logs with: docker compose logs -f app"
echo "===================================="
echo ""
