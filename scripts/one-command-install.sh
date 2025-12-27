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

log() { echo "[installer] $*"; }
error_exit() { echo "[installer][ERROR] $*" >&2; rollback; exit 1; }
trap 'on_error ${LINENO} ${?}' ERR
on_error() {
  rc=${2:-1}
  echo "[installer][ERROR] Error at line ${1:-unknown}, code ${rc}. Initiating rollback." >&2
  rollback
  exit ${rc}
}

# Prevent running unverified piped installs by default
if [ -z "${ALLOW_PIPED_INSTALL:-}" ] && [ ! -t 0 ]; then
  echo "Refusing to run directly from pipe for safety. Download the script and verify its checksum before running." >&2
  echo "See README.md for verification instructions." >&2
  exit 2
fi

log "Billing-Panel one-command script (action=$ACTION)"

# OS detection (only Debian/Ubuntu supported)
if [ -f /etc/os-release ]; then
  . /etc/os-release
  case "${ID:-}" in
    ubuntu|debian) : ;;
    *) error_exit "Unsupported OS: ${ID}. Supported: ubuntu, debian." ;;
  esac
else
  error_exit "Cannot detect OS. Aborting."
fi

# Verify docker daemon is running
if ! docker info >/dev/null 2>&1; then
  error_exit "Docker daemon does not appear to be running. Start Docker and retry (e.g., 'sudo systemctl start docker')."
fi

# Simple FQDN validation (allow localhost for testing)
validate_fqdn() {
  local fqdn="$1"
  if [ "${fqdn}" = "localhost" ]; then
    return 0
  fi
  if ! echo "$fqdn" | grep -Eq '^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$'; then
    return 1
  fi
  return 0
}

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

ACTIONS_PERFORMED=()
record_action() {
  ACTIONS_PERFORMED+=("$1")
}

wait_for_app_healthy() {
  log "Bringing up containers (build if needed)..."
  docker compose up -d --build
  record_action "compose_up"

  log "Waiting for app container to become healthy (timeout 120s)..."
  CONTAINER_ID=$(docker compose ps -q app || true)
  if [ -z "$CONTAINER_ID" ]; then
    error_exit "Unable to find app container after compose up. Aborting."
  fi

  for i in $(seq 1 60); do
    STATUS=$(docker inspect -f '{{.State.Health.Status}}' "$CONTAINER_ID" 2>/dev/null || echo "unknown")
    if [ "$STATUS" = "healthy" ]; then
      log "App container healthy"
      return
    fi
    log "Waiting for app (status=$STATUS), retrying..."
    sleep 2
  done
  error_exit "App container did not report healthy within timeout."
}

run_migrations_and_seed() {
  log "Running migrations inside app container (no silent failures)..."
  docker compose exec -T app php artisan migrate --force
  log "Running database seeds..."
  docker compose exec -T app php artisan db:seed --force
  record_action "migrations_and_seed"
}

install() {
  prompt_fqdn_if_missing
  if ! validate_fqdn "$FQDN"; then
    error_exit "Invalid FQDN provided: $FQDN"
  fi
  log "Using FQDN: $FQDN"

  # If services exist, optionally stop and remove
  if docker compose ps -q | grep -q .; then
    log "Existing compose services detected."
    if confirm "An existing installation appears to be present. Stop and remove existing containers/volumes before installing (destructive)?"; then
      log "Stopping and removing existing compose stack and named volumes..."
      docker compose down --volumes --remove-orphans
      record_action "compose_down"
    else
      log "Skipping removal of existing stack. Proceeding with install (may upgrade or cause conflicts)."
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

rollback() {
  log "Rollback: cleaning up resources..."
  # Stop compose if it was started
  if printf '%s\n' "${ACTIONS_PERFORMED[@]:-}" | grep -q "compose_up"; then
    log "Stopping compose stack..."
    docker compose down --volumes --remove-orphans || log "Failed to fully remove compose stack during rollback"
  fi
  # If migrations were applied, do not attempt to revert automatically; warn the operator
  if printf '%s\n' "${ACTIONS_PERFORMED[@]:-}" | grep -q "migrations_and_seed"; then
    log "Migrations were applied before failure. Manual intervention may be required to revert database changes."
  fi
  log "Rollback complete."
}

uninstall() {
  echo "Uninstall will stop the stack and remove containers and named volumes. Back up any data first."
  if ! confirm "Are you sure you want to uninstall and delete volumes? This cannot be undone."; then
    echo "Uninstall aborted by user."; return
  fi
  echo "Stopping and removing compose stack and named volumes..."
  docker compose down --volumes --remove-orphans
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