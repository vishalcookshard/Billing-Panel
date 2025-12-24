#!/usr/bin/env bash
set -euo pipefail

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

error_exit() {
  echo -e "${RED}✗ ERROR: $1${NC}" >&2
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

validate_domain() {
  local domain=$1
  
  if [[ ! "$domain" =~ \. ]]; then
    error_exit "Invalid domain: '$domain'\nMust be valid FQDN (e.g., billing.example.com)"
  fi
  
  if [[ "$domain" == *"://"* ]]; then
    error_exit "Invalid domain: '$domain'\nDon't include http:// or https://"
  fi
  
  if [[ ! "$domain" =~ ^[a-zA-Z0-9.-]+$ ]]; then
    error_exit "Invalid domain: '$domain'\nOnly alphanumeric characters, dots, and hyphens allowed"
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
  echo ""
  echo "  1) Install Billing-Panel"
  echo "  2) Uninstall Billing-Panel"
  echo "  3) Exit"
  echo ""
  read -p "Enter your choice (1-3): " choice </dev/tty
}

# ============================================================================
# INSTALL FUNCTION
# ============================================================================

install_billing_panel() {
  trap 'error_exit "Installation failed at line $LINENO"' ERR
  
  section "BILLING-PANEL INSTALLATION"
  
  # Root check
  [[ $EUID -ne 0 ]] && error_exit "Must run as root (use: sudo bash manage.sh)"
  success "Running as root"
  
  # Disk space check
  local available_space=$(df / | awk 'NR==2 {print $4}')
  [[ "$available_space" -lt 5242880 ]] && error_exit "Need 5GB+ disk space (have: $((available_space / 1048576))GB)"
  success "Disk space: $((available_space / 1048576))GB available"
  
  # Get domain
  section "CONFIGURATION"
  local domain
  read -p "Enter your domain (e.g., billing.example.com): " domain </dev/tty
  domain=$(validate_domain "$domain")
  domain="${domain#https://}"
  domain="${domain#http://}"
  domain="${domain%/}"
  success "Domain: $domain"
  
  # Confirm installation
  echo ""
  if ! confirm "Install Billing-Panel on $domain?"; then
    error_exit "Installation cancelled"
  fi
  
  section "INSTALLING DEPENDENCIES"
  
  # Remove conflicting services
  info "Removing conflicting services..."
  systemctl disable nginx 2>/dev/null || true
  apt purge -y nginx nginx-common 2>/dev/null || true
  success "Cleaned old services"
  
  # Update system
  info "Updating system packages..."
  apt-get update > /dev/null 2>&1
  apt-get install -y --no-install-recommends \
    ca-certificates curl gnupg lsb-release git > /dev/null 2>&1
  success "System packages updated"
  
  # Install Docker
  if ! command -v docker >/dev/null 2>&1; then
    info "Installing Docker..."
    mkdir -p /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg 2>/dev/null | \
      gpg --dearmor -o /etc/apt/keyrings/docker.gpg 2>/dev/null
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | \
      tee /etc/apt/sources.list.d/docker.list > /dev/null 2>&1
    apt-get update > /dev/null 2>&1
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin > /dev/null 2>&1
    success "Docker installed"
  else
    success "Docker already installed"
  fi
  
  systemctl enable docker > /dev/null 2>&1
  systemctl start docker > /dev/null 2>&1
  success "Docker service running"
  
  section "SETTING UP APPLICATION"
  
  # Clone repo with retries
  info "Cloning repository..."
  
  # Ensure parent directory exists
  local parent_dir=$(dirname "$INSTALL_DIR")
  if [[ ! -d "$parent_dir" ]]; then
    info "Creating directory $parent_dir..."
    mkdir -p "$parent_dir" || error_exit "Failed to create $parent_dir. Check permissions."
  fi
  
  # Remove existing installation if present
  [[ -d "$INSTALL_DIR" ]] && rm -rf "$INSTALL_DIR"
  
  local clone_success=0
  local max_retries=3
  local retry=0
  
  while [ $retry -lt $max_retries ]; do
    local git_output
    if git_output=$(git clone -b "$REPO_BRANCH" "$REPO_URL" "$INSTALL_DIR" 2>&1); then
      clone_success=1
      break
    else
      retry=$((retry + 1))
      if [ $retry -lt $max_retries ]; then
        info "Clone attempt $retry failed, retrying in 5 seconds..."
        sleep 5
      fi
    fi
  done
  
  if [ $clone_success -eq 0 ]; then
    error_exit "Failed to clone repository after $max_retries attempts. Check internet connectivity:\n$git_output"
  fi
  
  if [[ ! -d "$INSTALL_DIR" ]]; then
    error_exit "Repository clone failed. Directory $INSTALL_DIR not created."
  fi
  
  cd "$INSTALL_DIR" || error_exit "Failed to change to $INSTALL_DIR"
  success "Repository cloned to $INSTALL_DIR"
  
  # Create .env
  info "Configuring environment..."
  local app_key=$(openssl rand -base64 32)
  cat > .env <<EOF
APP_NAME="Billing Panel"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://$domain
APP_KEY=$app_key

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=billing
DB_USERNAME=root
DB_PASSWORD=secret

QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379

MAIL_MAILER=log
EOF
  success ".env file created"
  
  # Create Caddyfile (it's in .gitignore, so we generate it)
  info "Generating web server configuration..."
  cat > Caddyfile <<EOF
# Caddy configuration for Billing-Panel
$domain {
	# Serve static files from public directory
	root * /var/www/public

	# Route PHP files to PHP-FPM
	php_fastcgi app:9000 {
		# Timeouts for long-running requests
		timeout 300s
		read_timeout 300s
		write_timeout 300s
	}

	# Enable file serving
	file_server

	# Security headers
	header {
		X-Frame-Options "SAMEORIGIN"
		X-Content-Type-Options "nosniff"
		X-XSS-Protection "1; mode=block"
		Referrer-Policy "strict-origin-when-cross-origin"
		X-Permitted-Cross-Domain-Policies "none"
	}

	# Compression
	encode gzip

	# Handle 404s by routing to index.php (SPA/Laravel)
	try_files {path} {path}/ /index.php?{query}

	# Log errors
	log {
		output stdout
		level debug
	}
}
EOF
  success "Web server configured"
  
  section "STARTING SERVICES"
  
  info "Building and starting containers (this may take 2-3 minutes)..."
  if ! docker compose up -d --build 2>&1; then
    error_exit "Docker compose failed to start containers. Run 'docker compose up' manually to inspect the error."
  fi

  # Verify the main app container is running
  local app_container_id
  app_container_id=$(docker ps --filter "name=billing-panel-app" --filter "status=running" -q | head -n1 || true)
  if [[ -z "$app_container_id" ]]; then
    error_exit "billing-panel-app container is not running. Check 'docker ps' and container logs: docker logs billing-panel-app"
  fi
  success "Docker containers started"
  
  # Wait for database
  info "Waiting for database..."
  local max_attempts=30
  local attempt=0
  while [ $attempt -lt $max_attempts ]; do
    if docker exec billing-panel-db mysql -u root -psecret -e "SELECT 1" > /dev/null 2>&1; then
      success "Database ready"
      break
    fi
    attempt=$((attempt + 1))
    sleep 2
    echo -n "."
  done
  echo ""
  
  [[ $attempt -eq $max_attempts ]] && error_exit "Database startup timeout"
  
  section "CONFIGURING DATABASE"
  
  info "Running migrations..."
  # If the Laravel `artisan` file doesn't exist inside the container, skip migrations/seeding
  if docker exec -w /var/www billing-panel-app test -f artisan >/dev/null 2>&1; then
    if ! docker exec -w /var/www billing-panel-app php artisan migrate --force 2>&1; then
      error_exit "Database migrations failed. Check container logs: docker logs billing-panel-app"
    fi
    success "Database migrations complete"

    info "Seeding database with initial data..."
    if ! docker exec -w /var/www billing-panel-app php artisan db:seed --force 2>&1; then
      error_exit "Database seeding failed. Check container logs: docker logs billing-panel-app"
    fi
    success "Database seeded with initial data"
  else
    info "artisan not found in container at /var/www; skipping migrations and seeding"
  fi
  
  # Success message
  section "INSTALLATION COMPLETE"
  echo ""
  echo -e "${GREEN}✓ Billing-Panel is ready!${NC}"
  echo ""
  echo "  📍 Access: ${CYAN}https://$domain${NC}"
  echo "  📧 Email:  admin@example.com"
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
