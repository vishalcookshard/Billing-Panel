#!/usr/bin/env bash
set -euo pipefail

# DRY-RUN test for the install/uninstall prompt
# Intended for CI and local dry-run testing. It does NOT perform destructive actions.

ACTION="${ACTION:-}"
if [ -z "$ACTION" ]; then
  read -r -p "Choose action [install/uninstall/exit]: " ACTION
fi

case "${ACTION}" in
  install|i)
    echo "[DRY-RUN] Action: install"
    echo "[DRY-RUN] Would run (remote): curl -fsSL -o one-command-install.sh https://raw.githubusercontent.com/isthisvishal/Billing-Panel/main/scripts/one-command-install.sh && curl -fsSL -o one-command-install.sh.sha256 https://raw.githubusercontent.com/isthisvishal/Billing-Panel/main/scripts/one-command-install.sh.sha256 && sha256sum --check one-command-install.sh.sha256 && sudo bash one-command-install.sh install"
    echo "[DRY-RUN] Would run (local): sudo bash scripts/one-command-install.sh install"
    ;;
  uninstall|u)
    echo "[DRY-RUN] Action: uninstall"
    echo "[DRY-RUN] Would run (remote): curl -fsSL -o one-command-install.sh https://raw.githubusercontent.com/isthisvishal/Billing-Panel/main/scripts/one-command-install.sh && curl -fsSL -o one-command-install.sh.sha256 https://raw.githubusercontent.com/isthisvishal/Billing-Panel/main/scripts/one-command-install.sh.sha256 && sha256sum --check one-command-install.sh.sha256 && sudo bash one-command-install.sh uninstall"
    echo "[DRY-RUN] Would run (local): sudo bash scripts/one-command-install.sh uninstall"
    ;;
  exit|e)
    echo "Exit"
    ;;
  *)
    echo "Unknown action: $ACTION" >&2
    exit 2
    ;;
esac

exit 0
