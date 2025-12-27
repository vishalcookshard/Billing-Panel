
#!/usr/bin/env bash
set -Eeuo pipefail

# Global error trap for cleanup/rollback
trap 'on_error ${LINENO} ${?}' ERR
on_error() {
  local rc=${2:-1}
  log_error "Error at line ${1:-unknown}, code ${rc}. Initiating rollback."
  rollback
  exit ${rc}
}

# Timestamped logging
log() { echo -e "[$(date +'%Y-%m-%d %H:%M:%S')] $*"; }
log_error() { echo -e "\033[0;31m[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $*\033[0m" >&2; }

# OS detection (Ubuntu/Debian only)
if [ -f /etc/os-release ]; then
  . /etc/os-release
  case "${ID:-}" in
    ubuntu|debian) : ;;
    *) log_error "Unsupported OS: ${ID}. Supported: ubuntu, debian."; exit 2 ;;
  esac
else
  log_error "Cannot detect OS. Aborting."; exit 2
fi

# Docker daemon health check
if ! command -v docker >/dev/null 2>&1; then
  log_error "Docker is required. Please install Docker."; exit 2
fi
if ! docker info >/dev/null 2>&1; then
  log_error "Docker daemon is not running. Start Docker and retry."; exit 2
fi

# Billing-Panel Manager Script
# Unified install, manage, and uninstall tool
# Usage: bash manage.sh

# ANSI Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
INSTALL_DIR="/opt/billing-panel"
REPO_URL="https://github.com/isthisvishal/Billing-Panel.git"
REPO_BRANCH="main"

# ============================================================================
# UTILITY FUNCTIONS
# ============================================================================


# Error exit with logging
error_exit() {
  log_error "$1"
  exit 1
}

success() {
  echo -e "${GREEN}✓ $1${NC}"
}

info() {
  echo -e "${CYAN}ℹ $1${NC}"
}

section() {
  echo ""
  echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
  echo -e "${BLUE}  $1${NC}"
  echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
  echo ""
}

confirm() {
  local prompt="$1"
  local response
  read -p "$(echo -e "${YELLOW}⚠ $prompt (yes/no): ${NC}")" response </dev/tty
  [[ "$response" == "yes" ]] && return 0 || return 1
}


# Strict FQDN validation
validate_domain() {
  local domain=$1
  if [[ -z "$domain" ]]; then
    error_exit "Domain cannot be empty."
  fi
  if [[ "$domain" == *"://"* ]]; then
    error_exit "Invalid domain: '$domain'. Do not include http:// or https://."
  fi
  if ! [[ "$domain" =~ ^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)+$ ]]; then
    error_exit "Invalid FQDN: '$domain'. Must be a valid domain (e.g., billing.example.com)"
  fi
  echo "$domain"
}

# ============================================================================
# MENU
# ============================================================================

show_menu() {
  clear
  echo ""
  echo -e "${CYAN}╔════════════════════════════════════════╗${NC}"
  echo -e "${CYAN}║    Billing-Panel Manager${NC}"
  echo -e "${CYAN}║    Production-Ready Billing System${NC}"
  echo -e "${CYAN}╚════════════════════════════════════════╝${NC}"
  echo ""
  echo "What would you like to do?"

  # Rollback logic for partial installs
  rollback() {
    log "[ROLLBACK] Cleaning up partial install..."
    if docker compose ps -q | grep -q .; then
      log "[ROLLBACK] Stopping and removing containers/volumes..."
      docker compose down --volumes --remove-orphans || log_error "[ROLLBACK] Failed to fully remove compose stack"
    fi
    log "[ROLLBACK] Rollback complete. Manual cleanup may be required for DB or files."
  }

  # Modular uninstall function
  uninstall_billing_panel() {
    section "UNINSTALL BILLING-PANEL"
    if ! confirm "Are you sure you want to uninstall and delete all containers/volumes? This cannot be undone."; then
      log "Uninstall cancelled by user."; return
    fi
    log "Stopping and removing containers/volumes..."
    docker compose down --volumes --remove-orphans
    log "Uninstall complete."
  }

  # Modular verify function (checks health of containers and DB)
  verify_billing_panel() {
    section "VERIFY BILLING-PANEL HEALTH"
    log "Checking Docker containers..."
    docker compose ps
    log "Checking app health endpoint..."
    if curl -fsSL https://localhost/api/health | grep -q 'ok'; then
      log "App health endpoint OK."
    else
      log_error "App health endpoint failed."
    fi
  }

  # Main menu logic
  main() {
    show_menu
    case "$choice" in
      1) install_billing_panel ;;
      2) uninstall_billing_panel ;;
      3) log "Exiting."; exit 0 ;;
      *) log_error "Invalid choice: $choice"; exit 1 ;;
    esac
  }

  main
  echo "  🔑 Password: password"
  echo ""
  echo "⚠️  IMPORTANT:"
  echo "  1. Change the default password immediately"
  echo "  2. Update admin email address"
  echo "  3. Configure your domain DNS to point to this server"
  echo "  4. Go to admin panel and create your service categories"
  echo ""
}

# ============================================================================
# UNINSTALL FUNCTION
# ============================================================================

uninstall_billing_panel() {
  section "BILLING-PANEL UNINSTALL"
  
  # Check if installed
  if [[ ! -d "$INSTALL_DIR" ]]; then
    error_exit "Billing-Panel not found at $INSTALL_DIR"
  fi
  
  success "Found installation at $INSTALL_DIR"
  
  # Final confirmation
  echo ""
  if ! confirm "Permanently delete Billing-Panel and all data?"; then
    error_exit "Uninstall cancelled"
  fi
  
  if ! confirm "This action CANNOT be undone. Delete everything?"; then
    error_exit "Uninstall cancelled"
  fi
  
  section "REMOVING APPLICATION"
  
  info "Stopping Docker containers..."
  cd "$INSTALL_DIR"
  docker compose down > /dev/null 2>&1 || true
  success "Containers stopped"
  
  info "Removing Docker volumes..."
  docker volume rm billing-panel-db_data 2>/dev/null || true
  docker volume rm billing-panel-caddy_data 2>/dev/null || true
  docker volume rm billing-panel-caddy_config 2>/dev/null || true
  success "Volumes removed"
  
  info "Deleting installation directory..."
  rm -rf "$INSTALL_DIR"
  success "Installation removed"
  
  section "UNINSTALL COMPLETE"
  echo ""
  echo -e "${GREEN}✓ Billing-Panel has been completely removed${NC}"
  echo ""
}

# ============================================================================
# MAIN
# ============================================================================

main() {
  while true; do
    show_menu
    
    case "$choice" in
      1)
        install_billing_panel
        read -p "Press Enter to return to menu..." </dev/tty
        ;;
      2)
        uninstall_billing_panel
        read -p "Press Enter to return to menu..." </dev/tty
        ;;
      3)
        echo ""
        echo -e "${CYAN}Goodbye!${NC}"
        exit 0
        ;;
      *)
        error_exit "Invalid choice"
        ;;
    esac
  done
}

main "$@"
