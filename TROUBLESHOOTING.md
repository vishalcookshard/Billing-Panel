# Troubleshooting Guide

This guide helps you fix common deployment errors.

## Before Installation

### Check System Requirements
- **Operating System**: Ubuntu 20.04 LTS or newer, Debian 11 or newer
- **Disk Space**: Minimum 5GB free
- **RAM**: Minimum 2GB (recommended 4GB+)
- **Internet**: Required for downloading Docker images and dependencies
- **Ports**: 80 (HTTP) and 443 (HTTPS) must be available

### Check Available Resources
```bash
# Check disk space
df -h /

# Check available RAM
free -h

# Check available CPU cores
nproc
```

---

## Common Errors During Installation

### ERROR 1: "This script must be run as root"
**Solution**: Run the installation with sudo:
```bash
sudo bash /path/to/scripts/install.sh
```

### ERROR 2: "Insufficient disk space"
**Solution**: Free up disk space or use a different partition:
```bash
# Check disk usage
du -sh /*

# Clean up package cache
apt-get clean
apt-get autoclean
```

### ERROR 3: "Failed to build Docker images"
**Possible Causes**:
- No internet connection
- Docker daemon not running
- Insufficient RAM
- Port 2375/2376 blocked

**Solutions**:
```bash
# Check internet connection
ping google.com

# Check Docker status
systemctl status docker

# Start Docker if stopped
systemctl start docker

# Check Docker daemon logs
journalctl -u docker -f

# Try installation again
bash /path/to/scripts/install.sh
```

### ERROR 4: "Database failed to start"
**Possible Causes**:
- Port 3306 already in use
- Insufficient disk space
- Docker volume permission issues
- Memory pressure

**Solutions**:
```bash
# Check if port 3306 is in use
netstat -tulpn | grep 3306

# Kill service using the port
lsof -ti :3306 | xargs kill -9

# Check Docker volumes
docker volume ls
docker volume prune

# Check disk space
df -h

# View database logs
docker compose logs db
```

### ERROR 5: "Application container failed to initialize"
**Possible Causes**:
- PHP extensions missing
- Insufficient memory
- composer.json dependency conflicts
- File permission issues

**Solutions**:
```bash
# View app logs
docker compose logs app

# Check PHP extensions
docker compose exec app php -m

# Rebuild containers with fresh images
cd /opt/billing-panel
docker compose down -v
docker compose up -d --build

# Wait 60 seconds then check
sleep 60
docker compose ps
```

### ERROR 6: "Failed to generate APP_KEY"
**Possible Causes**:
- PHP artisan not working
- .env file not readable
- Database not ready

**Solutions**:
```bash
# Check .env file permissions
ls -la /opt/billing-panel/.env

# Manually generate key
cd /opt/billing-panel
docker compose exec app php artisan key:generate --force

# Check if app container is healthy
docker compose exec app php artisan tinker
# Type: exit() and press Enter
```

### ERROR 7: "Migrations failed"
**Possible Causes**:
- Database not fully initialized
- Connection timeout
- Schema conflicts

**Solutions**:
```bash
# Wait for database to be fully ready
sleep 30

# Check database connection
docker compose exec app php artisan tinker
# In tinker:
DB::connection()->getPdo();
exit();

# Try migration again
docker compose exec app php artisan migrate --force

# If migration still fails, check the full error
docker compose exec app php artisan migrate --force 2>&1 | head -50
```

---

## Post-Installation Issues

### Can't access the application
**Possible Causes**:
- Caddy not running
- SSL certificate not generated
- DNS not pointing to server
- Firewall blocking ports

**Solutions**:
```bash
# Check if Caddy is running
docker compose ps web

# View Caddy logs
docker compose logs web

# Check certificate generation
ls -la /var/lib/docker/volumes/*/

# Try HTTP instead of HTTPS (temporary)
# Change APP_URL in .env to http://your-domain.com
# Then restart: docker compose restart

# Restart Caddy
docker compose restart web
```

### Database connection errors in app
**Solutions**:
```bash
# Check database is running
docker compose ps db

# Test database connection
docker compose exec db mysql -u root -p$DB_PASSWORD -e "SHOW DATABASES;"

# Check app environment variables
docker compose exec app env | grep DB_

# Restart app
docker compose restart app
```

### Slow application or timeouts
**Solutions**:
```bash
# Check system resources
top -b -n 1 | head -20

# Check Docker resource limits
docker stats

# Increase timeouts in Caddyfile
# Edit /opt/billing-panel/Caddyfile and increase:
# read_timeout 600s
# write_timeout 600s

# Restart web server
docker compose restart web
```

### Queue jobs not processing
**Solutions**:
```bash
# Check worker is running
docker compose ps worker

# View worker logs
docker compose logs -f worker

# Restart worker
docker compose restart worker

# Check if jobs are in queue
docker compose exec app php artisan queue:failed
```

---

## Recovery Steps

### Full Restart
```bash
cd /opt/billing-panel
docker compose down
docker compose up -d --build
```

### Clean Start (removes all data)
```bash
cd /opt/billing-panel
docker compose down -v  # WARNING: This deletes database!
docker system prune -a
docker compose up -d --build
```

### View All Logs
```bash
# All services
docker compose logs

# Specific service
docker compose logs app      # Application
docker compose logs db       # Database
docker compose logs web      # Web server
docker compose logs worker   # Queue worker
docker compose logs redis    # Cache/Queue backend
```

### SSH into Container
```bash
# Application
docker compose exec app bash

# Database
docker compose exec db bash

# View files
docker compose exec app ls -la /var/www
```

---

## Performance Optimization

### If installation is slow:
1. Check internet speed: `speedtest-cli` or `wget -O /dev/null http://speedtest.net`
2. Use a faster mirror for apt: Edit `/etc/apt/sources.list`
3. Increase Docker memory: Edit `/etc/docker/daemon.json`

### Increase Docker Memory Limit
```bash
cat > /etc/docker/daemon.json <<EOF
{
  "storage-driver": "overlay2",
  "log-driver": "json-file",
  "memory": "4g"
}
EOF

systemctl restart docker
```

---

## Contact Support

If you still have issues:
1. Collect logs: `docker compose logs > logs.txt`
2. Check disk space: `df -h`
3. Check memory: `free -h`
4. View system logs: `journalctl -xe`
5. Open an issue on GitHub with logs attached

---

## Additional Commands

### Useful Docker Commands
```bash
# View running containers
docker compose ps

# Stop all services
docker compose stop

# Remove containers but keep volumes
docker compose down

# Remove everything (containers, volumes, networks)
docker compose down -v

# Rebuild specific service
docker compose up -d --build app

# View resource usage
docker stats

# Clean up unused resources
docker system prune
```

### Laravel/Artisan Commands
```bash
# Run database migrations
docker compose exec app php artisan migrate

# Clear cache
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear

# View environment
docker compose exec app php artisan env

# Test database connection
docker compose exec app php artisan tinker
```

---

Last updated: 2025-12-24
