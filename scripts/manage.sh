

#!/usr/bin/env bash
set -Eeuo pipefail

INSTALL_DIR="/opt/Billing-Panel"
REPO_URL="https://github.com/isthisvishal/Billing-Panel.git"
REPO_BRANCH="main"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

rollback() {
    log "Rolling back installation..."
    if [[ -d "$INSTALL_DIR" ]]; then
        sudo rm -rf "$INSTALL_DIR"
        log "Removed $INSTALL_DIR"
    fi
}

install_billing_panel() {
    while true; do
        read -p "Enter your domain (e.g., billing.example.com): " FQDN </dev/tty
        if [[ "$FQDN" =~ ^[a-zA-Z0-9.-]+$ ]] && [[ ! "$FQDN" =~ [[:space:]] ]] && [[ ! "$FQDN" =~ :// ]]; then
            break
        else
            echo "Invalid domain. Please enter a valid domain (letters, numbers, dots, hyphens only)."
        fi
    done

    echo "\nSummary:"
    echo "Domain: $FQDN"
    echo "Install dir: $INSTALL_DIR"
    echo "Docker containers will be created."
    read -p "Proceed with installation? (yes/no): " response </dev/tty
    if [[ "$response" != "yes" ]]; then
        return
    fi

    if [[ -d "$INSTALL_DIR" ]]; then
        read -p "$INSTALL_DIR already exists. Remove and reinstall? (yes/no): " reinstall </dev/tty
        if [[ "$reinstall" == "yes" ]]; then
            sudo rm -rf "$INSTALL_DIR"
        else
            return
        fi
    fi

    sudo mkdir -p "$INSTALL_DIR"
    cd "$INSTALL_DIR"

    if ! command -v git >/dev/null 2>&1; then
        echo "git is required. Please install git."
        rollback
        return
    fi

    if ! command -v docker >/dev/null 2>&1; then
        echo "Docker is required. Please install Docker."
        rollback
        return
    fi

    if ! command -v docker compose >/dev/null 2>&1; then
        echo "Docker Compose v2+ is required. Please install Docker Compose."
        rollback
        return
    fi

    log "Cloning repository..."
    if ! git clone --branch "$REPO_BRANCH" "$REPO_URL" .; then
        echo "Failed to clone repository."
        rollback
        return
    fi

    if [[ ! -f .env ]]; then
        cp .env.example .env
    fi
    sed -i "s|^APP_URL=.*|APP_URL=https://$FQDN|" .env

    log "Validating docker compose config..."
    if ! sudo docker compose config; then
        echo "docker compose config failed."
        rollback
        return
    fi

    log "Starting containers..."
    if ! sudo docker compose up -d --build; then
        echo "docker compose up failed."
        rollback
        return
    fi

    log "Waiting for app container health (max 120s)..."
    for i in {1..24}; do
        health=$(sudo docker inspect --format='{{.State.Health.Status}}' $(sudo docker compose ps -q app) 2>/dev/null || echo "unknown")
        if [[ "$health" == "healthy" ]]; then
            break
        fi
        sleep 5
    done
    if [[ "$health" != "healthy" ]]; then
        echo "App container did not become healthy."
        rollback
        return
    fi

    log "Running migrations..."
    if ! sudo docker compose exec -T app php artisan migrate --force; then
        echo "Migration failed."
        rollback
        return
    fi

    log "Seeding database..."
    if ! sudo docker compose exec -T app php artisan db:seed --force; then
        echo "Seeding failed."
        rollback
        return
    fi

    echo "\nInstallation complete!"
    echo "URL: https://$FQDN"
    echo "Default credentials:"
    echo "  Email: admin@example.com"
    echo "  Password: password"
    echo "\nSECURITY WARNING:"
    echo "- Change the admin password immediately."
    echo "- Update the admin email."
    echo "- Configure DNS for $FQDN."
    echo "- Create service categories."
    read -p "Press Enter to return to menu..." </dev/tty
}

uninstall_billing_panel() {
    if [[ ! -d "$INSTALL_DIR" ]]; then
        echo "$INSTALL_DIR not found. Nothing to uninstall."
        read -p "Press Enter to return to menu..." </dev/tty
        return
    fi
    echo "WARNING: This will permanently delete all Billing-Panel data, containers, and volumes!"
    read -p "Are you sure? (yes/no): " response </dev/tty
    if [[ "$response" != "yes" ]]; then
        return
    fi
    read -p "Type yes again to confirm: " response </dev/tty
    if [[ "$response" != "yes" ]]; then
        return
    fi
    cd "$INSTALL_DIR"
    sudo docker compose down --volumes --remove-orphans || true
    for v in billing-panel-db_data billing-panel-caddy_data billing-panel-caddy_config; do
        sudo docker volume rm -f "$v" 2>/dev/null || true
    done
    cd /
    sudo rm -rf "$INSTALL_DIR"
    echo "Uninstall complete."
    read -p "Press Enter to return to menu..." </dev/tty
}

show_menu() {
    clear
    echo "╔════════════════════════════════════════╗"
    echo "║ Billing-Panel Manager                  ║"
    echo "║ Production-Ready Billing System        ║"
    echo "╚════════════════════════════════════════╝"
    echo "1) Install Billing-Panel"
    echo "2) Uninstall Billing-Panel"
    echo "3) Exit"
}

main() {
    trap 'echo; echo "Aborted."; exit 130' INT
    trap 'echo; echo "An error occurred. Exiting."; exit 1' ERR
    while true; do
        show_menu
        read -p "Enter your choice [1-3]: " choice </dev/tty
        case "$choice" in
            1) install_billing_panel ;;
            2) uninstall_billing_panel ;;
            3) exit 0 ;;
            *) echo "Invalid choice. Please try again."; sleep 1 ;;
        esac
    done
}

main "$@"
