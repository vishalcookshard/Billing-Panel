# Billing-Panel

A modern, production-ready billing and hosting management system built with Laravel. Perfect for hosting providers, SaaS companies, and digital service businesses.

---

## 🚀 One-Line Install (Recommended)

**Requirements:**
- Ubuntu 20.04+ or Debian 11+ (Ubuntu 22.04 recommended)
- Docker Engine 20.10+ and Docker Compose v2+
- 2GB+ RAM (4GB+ recommended)
- 5GB+ free disk space
- Root access (sudo)
- A domain name (e.g., billing.example.com) pointed to your server's IP

**Install in one command:**

```bash
curl -fsSL -o one-command-install.sh https://raw.githubusercontent.com/isthisvishal/Billing-Panel/main/scripts/one-command-install.sh \
   && curl -fsSL -o one-command-install.sh.sha256 https://raw.githubusercontent.com/isthisvishal/Billing-Panel/main/scripts/one-command-install.sh.sha256 \
   && sha256sum --check one-command-install.sh.sha256 \
   && sudo bash one-command-install.sh install
```

The script will prompt for your domain (FQDN) and confirm before any destructive actions. For non-interactive install:

```bash
FQDN=billing.example.com YES=1 sudo bash one-command-install.sh install
```

---

## Default Admin Credentials

- Email: admin@example.com
- Password: password

**Change these immediately after first login!**

---

## Uninstall

To uninstall (removes containers and volumes):

```bash
curl -fsSL -o one-command-install.sh https://raw.githubusercontent.com/isthisvishal/Billing-Panel/main/scripts/one-command-install.sh \
   && curl -fsSL -o one-command-install.sh.sha256 https://raw.githubusercontent.com/isthisvishal/Billing-Panel/main/scripts/one-command-install.sh.sha256 \
   && sha256sum --check one-command-install.sh.sha256 \
   && sudo bash one-command-install.sh uninstall
```

---

## After Installation

1. Access your panel: `https://your-domain.com`
2. Login with default admin credentials
3. Change admin password and email
4. Configure DNS and create service categories

---

## Troubleshooting

- `docker ps` — Check running containers
- `docker compose logs -f` — View logs
- `docker compose exec app php artisan migrate --force` — Run migrations manually

---

A modern, production-ready billing and hosting management system built with Laravel. Perfect for hosting providers, SaaS companies, and digital service businesses.

## 🚀 One-Line Installation (VM/Server)

Run this single command to install the panel. The script is interactive and will ask for your FQDN and confirm any destructive actions:

```bash
# Download, verify checksum, then run locally (recommended):
curl -fsSL -o one-command-install.sh https://raw.githubusercontent.com/isthisvishal/Billing-Panel/main/scripts/one-command-install.sh
curl -fsSL -o one-command-install.sh.sha256 https://raw.githubusercontent.com/isthisvishal/Billing-Panel/main/scripts/one-command-install.sh.sha256
sha256sum --check one-command-install.sh.sha256 && sudo bash one-command-install.sh install
```

### Manual installation (step-by-step)

If you prefer to install manually, follow these steps on the target host:

### Manual Installation (Step-by-Step)

#### Prerequisites
- Linux server (Ubuntu 20.04+/Debian 11+ recommended)
- Docker Engine 20.10+ and Docker Compose v2+
- Open ports: 80, 443 (firewall/DNS must point to your server)
- At least 2GB RAM, 5GB+ disk

#### 1. Clone the repository
```bash
sudo git clone https://github.com/isthisvishal/Billing-Panel.git /opt/billing-panel
cd /opt/billing-panel
```

#### 2. Configure environment
```bash
cp .env.example .env
# Set your domain:
sed -i "s|^APP_URL=.*|APP_URL=https://your.domain.com|" .env
# (Optional) Set DB credentials to match docker-compose.yml
# DB_DATABASE=billing
# DB_USERNAME=billing
# DB_PASSWORD=ChangeThisToAStrongPassword1234
```

#### 3. Build and start containers
```bash
sudo docker compose up -d --build
```

#### 4. First-run checklist (run these in /opt/billing-panel):
```bash
# Ensure permissions (should be correct from Dockerfile, but verify if using custom volumes):
sudo chown -R 1000:1000 storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Generate app key if missing:
sudo docker compose exec app php artisan key:generate --force

# Run migrations:
sudo docker compose exec app php artisan migrate --force

# (Optional) Seed database:
sudo docker compose exec app php artisan db:seed --force

# Cache config/routes for production:
sudo docker compose exec app php artisan config:cache
sudo docker compose exec app php artisan route:cache
```

#### 5. Access the panel
Visit: https://your.domain.com

#### 6. Default admin credentials
- Email: admin@example.com
- Password: password

**Change these immediately after first login!**

---

### Environment Variables Explained

| Variable         | Description                                 | Example                        |
|------------------|---------------------------------------------|--------------------------------|
| APP_URL          | Full URL to your panel                      | https://billing.example.com    |
| DB_HOST          | Database host (container name or IP)        | db                             |
| DB_DATABASE      | Database name                               | billing                        |
| DB_USERNAME      | Database user                               | billing                        |
| DB_PASSWORD      | Database password                           | ChangeThisToAStrongPassword1234|
| QUEUE_CONNECTION | Queue backend                               | redis                          |
| REDIS_HOST       | Redis host                                  | redis                          |
| REDIS_PORT       | Redis port                                  | 6379                           |

---

### First-Run Troubleshooting

- **500 Error after install:**
  - Check logs: `sudo docker compose logs app`
  - Check Laravel logs: `sudo docker compose exec app tail -n 50 storage/logs/laravel.log`
  - Ensure DB credentials in `.env` match those in `docker-compose.yml`
  - Ensure `APP_KEY` is set in `.env`
  - Ensure permissions: `sudo chown -R 1000:1000 storage bootstrap/cache && sudo chmod -R 775 storage/bootstrap/cache`

- **Database connection/auth errors:**
  - Check DB logs: `sudo docker compose logs db`
  - Ensure DB user/password/database match in `.env` and `docker-compose.yml`
  - Never use root for production DB access

- **Config cache errors:**
  - If `php artisan config:cache` fails, check for closures in config files (see `config/sentry.php`)

- **Caddy/PHP-FPM errors:**
  - Check web logs: `sudo docker compose logs web`
  - Ensure Caddyfile points to `/var/www/public/index.php`

- **Permissions errors:**
  - Ensure storage and bootstrap/cache are owned by the app user (UID 1000 by default)

---

### Safe Updates & Restarts

To update:
```bash
cd /opt/billing-panel
sudo git pull
sudo docker compose pull
sudo docker compose up -d --build
sudo docker compose exec app php artisan migrate --force
sudo docker compose exec app php artisan config:cache
sudo docker compose exec app php artisan route:cache
```

To restart:
```bash
sudo docker compose restart
```

---

### Common Errors & Fixes

| Error                        | Cause/Diagnosis                                   | Fix Command(s) |
|------------------------------|---------------------------------------------------|----------------|
| 500 Internal Server Error    | Laravel misconfig, DB not ready, missing APP_KEY  | See above      |
| SQLSTATE[HY000] [1045]       | DB auth failed (user/pass mismatch)               | Check .env     |
| Permission denied            | storage/ or bootstrap/cache not writable          | chown/chmod    |
| config:cache fails           | Closure in config file                            | Remove closure |
| Caddy 502/Bad Gateway        | PHP-FPM not running or misconfigured              | Check logs     |

---

### Service Overview

- **app**: Laravel PHP-FPM application
- **web**: Caddy web server (HTTPS, reverse proxy)
- **db**: MariaDB database
- **redis**: Redis for queues/cache
- **worker**: Laravel queue worker
- **scheduler**: Laravel scheduler

---

### First-Run Checklist

- [ ] Docker and Docker Compose installed
- [ ] Ports 80/443 open and DNS set
- [ ] .env configured (APP_URL, DB_*, etc.)
- [ ] Permissions on storage/ and bootstrap/cache
- [ ] APP_KEY set
- [ ] Migrations run
- [ ] Config and route cache built
- [ ] Default admin password changed

---
