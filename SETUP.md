# Billing-Panel Setup Guide

## Quick Start

### One-Click Installation (Ubuntu/Debian)

```bash
curl -fsSL https://github.com/isthisvishal/Billing-Panel/raw/main/scripts/install.sh | sudo bash
```

The installer will:
- Install Docker and Docker Compose if needed
- Clone the repository
- Create .env configuration
- Generate SSL certificate via Caddy
- Run database migrations
- Start all services

## Manual Setup

### Prerequisites
- Docker & Docker Compose
- Ubuntu 20.04+ or equivalent Linux
- Domain name with DNS pointing to your server
- Root or sudo access

### Step 1: Clone Repository
```bash
git clone https://github.com/isthisvishal/Billing-Panel.git /opt/billing-panel
cd /opt/billing-panel
```

### Step 2: Configure Environment
```bash
cp .env.example .env
# Edit .env with your settings
nano .env
```

Key variables to update:
- `APP_URL`: Your domain (https://billing.example.com)
- `DB_PASSWORD`: Secure database password
- `STRIPE_*`: Stripe API keys (if using Stripe)
- `DISCORD_WEBHOOK_URL`: Discord integration (optional)

### Step 3: Start Services
```bash
docker compose up -d --build
```

Wait for all containers to be healthy:
```bash
docker compose ps
```

### Step 4: Initialize Database
```bash
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --force
docker compose exec app php artisan app:install
```

### Step 5: Access Application
Visit `https://your-domain.com` in your browser.

## Service Architecture

| Service | Purpose | Port |
|---------|---------|------|
| **app** | Laravel application | 9000 |
| **web** | Caddy reverse proxy & SSL | 80, 443 |
| **db** | MariaDB database | 3306 |
| **redis** | Queue & cache | 6379 |
| **worker** | Background jobs | - |
| **scheduler** | Cron tasks | - |

## Common Commands

### View Logs
```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f app
docker compose logs -f db
```

### Database Access
```bash
docker compose exec db mysql -u root -p billing
```

### Execute Artisan Commands
```bash
docker compose exec app php artisan <command>
```

### Run Tests
```bash
docker compose exec app php artisan test
```

### Stop Services
```bash
docker compose down
```

### Restart Services
```bash
docker compose restart
```

### View Running Containers
```bash
docker compose ps
```

## Troubleshooting

### Database Connection Errors
```bash
# Check database health
docker compose exec db mysqladmin ping -u root -p

# Check logs
docker compose logs db
```

### Application Won't Start
```bash
# Check app logs
docker compose logs app

# Verify migrations ran
docker compose exec app php artisan migrate:status
```

### SSL Certificate Issues
```bash
# Check Caddy logs
docker compose logs web

# Verify domain DNS resolves
nslookup your-domain.com
```

### Queue Issues
```bash
# Check worker status
docker compose logs worker

# Check Redis connection
docker compose exec redis redis-cli ping
```

## Security Recommendations

1. **Change Database Password**: Update `DB_PASSWORD` in `.env`
2. **Enable Firewall**: Restrict access to database port 3306
3. **Use Strong APP_KEY**: Already generated via installer
4. **Regular Backups**: Backup `/opt/billing-panel/storage/` and database
5. **Keep Updated**: Run `git pull && docker compose up -d --build` regularly

## Backup & Restore

### Backup Database
```bash
docker compose exec db mysqldump -u root -p billing > backup.sql
```

### Restore Database
```bash
docker compose exec db mysql -u root -p billing < backup.sql
```

### Backup Application Files
```bash
tar -czf billing-panel-backup.tar.gz /opt/billing-panel
```

## Production Considerations

- Run behind a firewall
- Use strong passwords
- Enable automated backups
- Monitor logs regularly
- Keep system packages updated
- Consider using managed database service
- Use CDN for static assets
- Implement rate limiting

## Support

For issues or questions:
- Check logs: `docker compose logs -f`
- Review error messages carefully
- Visit: https://github.com/isthisvishal/Billing-Panel

---

**Last Updated**: December 24, 2025
