# Billing-Panel Quick Start Guide

Get your billing panel up and running in 5 minutes!

## Prerequisites

- Ubuntu 20.04+ or Debian 11+ VPS
- 2GB+ RAM minimum (4GB+ recommended)
- 5GB+ free disk space
- Root access via SSH
- A domain name (e.g., `billing.example.com`)

## Step 1: Buy a VPS

Choose any VPS provider:
- DigitalOcean
- Linode
- Vultr
- AWS
- Hetzner
- Any Linux provider

**Recommended Specs:**
- 2GB RAM, 50GB SSD, 2TB bandwidth
- Ubuntu 22.04 LTS

## Step 2: Point Your Domain

Add an A record to your domain's DNS pointing to your VPS IP:

```
Type: A
Name: billing (or your subdomain)
Value: your.vps.ip.address
TTL: 3600
```

Wait 5-15 minutes for DNS propagation.

## Step 3: SSH into Your VPS

```bash
ssh root@your.vps.ip.address
```

## Step 4: Run the Installation Script

The script handles everything:

```bash
sudo bash <(curl -fsSL https://raw.githubusercontent.com/isthisvishal/Billing-Panel/main/scripts/manage.sh)
```

When prompted:
- Choose **Option 1** to Install
- Enter your domain (e.g., `billing.example.com`)
- Confirm the installation

Note: The installer now fully configures the environment, runs migrations, creates an admin account, and starts queues and the scheduler. It will also verify background services (worker and scheduler) are running and that queue connectivity is available in production.

The script will:
- ✓ Install Docker
- ✓ Clone the repository
- ✓ Configure HTTPS
- ✓ Set up database
- ✓ Create admin account
- ✓ Initialize sample data

⏱️ **Takes 3-5 minutes**

## Step 5: Access Your Billing Panel

Open in your browser:
```
https://billing.example.com
```

**Default Credentials:**
- Email: `admin@example.com`
- Password: `password`

## Step 6: Initial Setup

### 1. Change Admin Password
- Login with default credentials
- Click your name in top-right
- Change password immediately

### 2. Create Service Categories

Go to **Admin Panel → Categories**

Click **Add Category**

Example:
```
Name: VPS Hosting
Slug: vps-hosting
Description: High-performance virtual servers
Active: Yes
```

Click **Save**

### 3. Create Plans

Go to **Admin Panel → Plans**

Click **Add Plan**

Example:
```
Category: VPS Hosting
Name: Starter VPS
Slug: starter
Description: Perfect for beginners
Price (Monthly): $9.99
Price (Yearly): $99.99
Features:
  - 1 vCPU
  - 2GB RAM
  - 50GB SSD
  - DDoS Protection
Active: Yes
```

Click **Save**

### 4. Update Custom Pages

Go to **Admin Panel → Pages**

Edit **Privacy Policy**, **Terms of Service**, and **FAQ** with your content.

### 5. Check Your Shop

Visit `https://billing.example.com/shop`

You'll see your service categories and plans ready for purchase!

## Common Tasks

### Check Logs

```bash
# App logs
docker logs billing-panel-app

# Web server logs
docker logs billing-panel-web

# Database logs
docker logs billing-panel-db
```

### Backup Database

```bash
docker exec billing-panel-db mysqldump -u root -psecret billing > backup.sql
```

### Restore Database

```bash
docker exec -i billing-panel-db mysql -u root -psecret billing < backup.sql
```

### Restart Services

```bash
cd /opt/billing-panel && docker compose restart
```

### Update Installation

```bash
cd /opt/billing-panel && docker compose pull && docker compose up -d
```

## Uninstall

To remove everything:

```bash
sudo bash /opt/billing-panel/scripts/manage.sh
# Choose Option 2 (Uninstall)
# Confirm twice
```

## Setting Up Email (Optional)

For transactional emails (invoices, notifications):

1. Get SMTP credentials from:
   - Mailtrap (free for development)
   - SendGrid
   - AWS SES
   - Gmail

2. Edit `/opt/billing-panel/.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@billing.example.com
MAIL_FROM_NAME="Billing Panel"
```

3. Restart Docker:

```bash
cd /opt/billing-panel && docker compose restart
```

## Setting Up Social Login (Optional)

### Google OAuth

1. Go to https://console.cloud.google.com
2. Create new project
3. Enable Google+ API
4. Create OAuth 2.0 credentials
5. Add redirect URI: `https://billing.example.com/auth/callback/google`
6. Copy Client ID and Secret
7. Add to admin panel settings

### Discord OAuth

1. Go to https://discord.com/developers/applications
2. Create New Application
3. Enable OAuth2
4. Add redirect URL: `https://billing.example.com/auth/callback/discord`
5. Copy Client ID and Secret
6. Add to admin panel settings

### GitHub OAuth

1. Go to https://github.com/settings/developers
2. Create OAuth App
3. Set callback URL: `https://billing.example.com/auth/callback/github`
4. Copy Client ID and Secret
5. Add to admin panel settings

## Troubleshooting

### "Domain already in use"
```bash
# Kill process on port 80/443
sudo lsof -i :80
sudo kill -9 <PID>
```

### "Cannot connect to Docker daemon"
```bash
sudo systemctl start docker
```

### "DNS not resolving"
- Wait 15 minutes for propagation
- Check DNS record: `nslookup billing.example.com`
- Contact your DNS provider if it doesn't resolve

### "HTTPS certificate error"
- Wait 2-3 minutes for Caddy to obtain certificate
- Check Caddy logs: `docker logs billing-panel-web`

### "Database connection error"
```bash
# Wait for database to be ready
docker logs billing-panel-db

# Check connectivity
docker exec billing-panel-app ping db
```

## Performance Tips

1. **Enable caching** - Set `CACHE_DRIVER=redis` in `.env`
2. **Optimize images** - Compress product images
3. **Use CDN** - CloudFlare free tier works great
4. **Monitor logs** - Check for errors regularly
5. **Backup regularly** - Daily database backups

## Security Checklist

- [ ] Changed default admin password
- [ ] Updated admin email address
- [ ] Configured firewall: `sudo ufw allow 80,443/tcp`
- [ ] Enabled HTTPS (automatic via Caddy)
- [ ] Set up regular backups
- [ ] Configured SMTP for emails
- [ ] Updated privacy policy and terms
- [ ] Tested checkout flow
- [ ] Verified admin access is secure

## Next Steps

1. Explore admin panel features
2. Create your service categories and plans
3. Add custom pages
4. Set up email notifications
5. Configure social logins
6. Test the complete purchase flow
7. Optimize your content and prices

## Getting Help

- 📖 Full documentation: Check README.md
- 🐛 Report bugs: GitHub Issues
- 💬 Ask questions: GitHub Discussions
- 🚀 Features: Submit feature requests

---

**Your billing panel is ready!** 🎉

Start selling your services today. Good luck! 🚀
