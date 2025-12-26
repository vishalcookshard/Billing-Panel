#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR=$(cd "$(dirname "$0")/.." && pwd)
cd "$ROOT_DIR"

# Simple installer/uninstaller for Billing-Panel
# Usage:
#  - Interactive install:        ./scripts/one-command-install.sh
#  - Non-interactive install:    ./scripts/one-command-install.sh install billing.example.com
#  - Interactive uninstall:      ./scripts/one-command-install.sh uninstall
#  - Non-interactive uninstall:  ./scripts/one-command-install.sh uninstall --yes
#  - Environment override:       FQDN=billing.example.com YES=1 ./scripts/one-command-install.sh install

ACTION="${1:-install}"
ARG2="${2:-}"
FQDN="${FQDN:-${ARG2:-}}"
AUTO_YES="${YES:-0}"

echo "Billing-Panel one-command script (action=$ACTION)"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker is required. Please install Docker and docker-compose." >&2
  exit 1
fi

confirm() {
  local prompt="$1"
  if [ "${AUTO_YES}" = "1" ] || [ "${AUTO_YES}" = "yes" ]; then
    return 0
  fi
  read -r -p "$prompt [y/N]: " ans
  case "$(echo "$ans" | tr '[:upper:]' '[:lower:]')" in
    y|yes) return 0 ;;
    *) return 1 ;;
  esac
}

prompt_fqdn_if_missing() {
  if [ -z "$FQDN" ]; then
    read -r -p "Enter the FQDN to use for this install (e.g. billing.example.com): " FQDN
  fi
  if [ -z "$FQDN" ]; then
    echo "No FQDN provided, aborting." >&2
    exit 1
  fi
}

set_app_url() {
  if [ ! -f .env ]; then
    cp .env.example .env || true
  fi
  if grep -q "^APP_URL=" .env 2>/dev/null; then
    sed -i -E "s|^APP_URL=.*|APP_URL=https://$FQDN|" .env
  else
    echo "APP_URL=https://$FQDN" >> .env
  fi
}

wait_for_app_healthy() {
  echo "Bringing up containers (build if needed)..."
  docker compose up -d --build

  echo "Waiting for app container to become healthy (timeout 120s)..."
  CONTAINER_ID=$(docker compose ps -q app || true)
  if [ -z "$CONTAINER_ID" ]; then
    echo "Unable to find app container; continuing and attempting to run migrations anyway."
    return
  fi

  for i in $(seq 1 60); do
    STATUS=$(docker inspect -f '{{.State.Health.Status}}' "$CONTAINER_ID" 2>/dev/null || echo "unknown")
    if [ "$STATUS" = "healthy" ]; then
      echo "App container healthy"
      return
    fi
    echo "Waiting for app (status=$STATUS), retrying..."
    sleep 2
  done
  echo "Warning: app container did not report healthy within timeout. Continuing..."
}

run_migrations_and_seed() {
  echo "Running migrations and seeding inside app container..."
  # tolerate failures (some optional packages may not be present during first-run)
  docker compose exec -T app php artisan migrate --force || true
  docker compose exec -T app php artisan db:seed --force || true
}

install() {
  prompt_fqdn_if_missing
  echo "Using FQDN: $FQDN"
  if docker compose ps -q | grep -q .; then
    echo "Existing compose services detected."
    if confirm "An existing installation appears to be present. Do you want to stop and remove existing containers/volumes before installing (destructive)?"; then
      echo "Stopping and removing existing compose stack and named volumes..."
      docker compose down --volumes --remove-orphans || true
    else
      echo "Skipping removal of existing stack. Proceeding with install (may upgrade or cause conflicts)."
    fi
  fi

  set_app_url
  wait_for_app_healthy
  run_migrations_and_seed

  echo "Install complete. Services started: web, app, worker, scheduler, db, redis"
  echo "Configured FQDN: https://$FQDN"
  echo "If using Caddy, it will attempt to obtain a certificate for $FQDN (ensure DNS points to this host)."
  echo "Admin user: admin@example.com / password (change immediately)"
}

uninstall() {
  echo "Uninstall will stop the stack and remove containers and named volumes. Back up any data first."
  if ! confirm "Are you sure you want to uninstall and delete volumes? This cannot be undone."; then
    echo "Uninstall aborted by user."; return
  fi
  echo "Stopping and removing compose stack and named volumes..."
  docker compose down --volumes --remove-orphans || true
  echo "Uninstall complete."
}

case "$ACTION" in
  install|i)
    if confirm "Proceed with installation?"; then
      install
    else
      echo "Installation cancelled."; exit 0
    fi
    ;;
  uninstall|remove|u)
    uninstall
    ;;
  help|-h|--help)
    cat <<'USAGE'
Usage: ./scripts/one-command-install.sh [install|uninstall] [fqdn]

Examples:
  ./scripts/one-command-install.sh                   # interactive: asks FQDN and confirmations
  ./scripts/one-command-install.sh install billing.example.com
  YES=1 FQDN=billing.example.com ./scripts/one-command-install.sh install
  ./scripts/one-command-install.sh uninstall         # interactive uninstall (asks confirmation)
  ./scripts/one-command-install.sh uninstall --yes  # non-interactive uninstall

You can also run:
  curl -sSL https://raw.githubusercontent.com/isthisvishal/Billing-Panel/main/scripts/one-command-install.sh | bash -s -- install
USAGE
    ;;
  *)
    echo "Unknown action: $ACTION" >&2; exit 1
    ;;
esac

echo "Done. To view logs: docker compose logs -f"