# Billing-Panel

A modern, production-ready billing and hosting management system built with Laravel. Perfect for hosting providers, SaaS companies, and digital service businesses.

## 🚀 One-Line Installation (VM/Server)

The installer will ask only for your FQDN (e.g. billing.example.com).

Interactive (recommended):

```bash
curl -sSL https://raw.githubusercontent.com/isthisvishal/Billing-Panel/main/scripts/one-command-install.sh | bash -s --
```

Non-interactive (provide FQDN):

```bash
bash -c "$(curl -sSL https://raw.githubusercontent.com/isthisvishal/Billing-Panel/main/scripts/one-command-install.sh)" billing.example.com
```

## ✨ Features

### Frontend
- **Professional Homepage** - Showcase your services with a modern design
- **Service Shop** - Browse and filter hosting plans by category
- **Service Categories** - VPS, Shared Hosting, Game Servers, Domains, etc.
- **Plan Listings** - Display detailed plans with pricing and features
- **User Dashboard** - Manage orders, billing, and account settings
- **Checkout System** - Secure payment flow (extensible to multiple gateways)
- **Public Pages** - Editable pages for Privacy, Terms, FAQ, etc.

### Admin Panel
- **Page Management** - Create, edit, and publish custom pages
- **Category Management** - Organize services into categories
- **Plan Management** - Create plans with flexible pricing (monthly, yearly, lifetime)
- **Order Tracking** - Monitor all customer orders and payments
- **User Management** - Manage customers and permissions

### Authentication
- **Email/Password Login** - Secure traditional authentication
- **Google OAuth** - One-click Google sign-in
- **Discord OAuth** - Community-focused sign-in
- **GitHub OAuth** - Developer-friendly authentication
- **Social Login Integration** - Seamless social account linking

### Technical Features
- **Docker & Docker Compose** - Production-ready containerization
- **Caddy Web Server** - Automatic HTTPS with renewal
- **MySQL Database** - Reliable data persistence
- **Redis Queue** - Background job processing
- **Laravel Framework** - Modern PHP with best practices
- **Bootstrap 5** - Responsive UI design
- **Zero-Configuration Install** - One script does everything

## 🚀 Quick Start

### One-Command Installation

Run the bundled installer which will prompt for your domain (FQDN):

```bash
sudo bash scripts/one-command-install.sh
```

Or run remotely in one line (interactive prompt for FQDN):

```bash
curl -sSL https://raw.githubusercontent.com/isthisvishal/Billing-Panel/main/scripts/one-command-install.sh | sudo bash -s --
```

To run non-interactively provide the FQDN as the first argument:

```bash
sudo bash scripts/one-command-install.sh billing.example.com
```

### System Requirements
- Ubuntu 20.04+ or Debian 11+
- 2GB+ RAM
- 5GB+ disk space
- Root access
- Valid domain name (FQDN, e.g., billing.example.com)

### Installation Steps

The installation script will:
1. Ask for your domain (only thing you need to provide)
2. Install Docker and dependencies
3. Clone the repository
4. Configure SSL with Caddy
5. Set up the database
6. Create default admin account
7. Initialize sample data

**Default Admin Credentials:**
- Email: `admin@example.com`
- Password: `password`

⚠️ Change these immediately after first login!

## 📋 Usage

### After Installation

1. **Access Your Panel**
   ```
   https://billing.example.com
   ```

2. **Login to Admin**
   - Go to `/admin/pages` in the URL (or use sidebar)
   - Use default credentials

3. **Set Up Your Services**
   - Create service categories (VPS, Shared Hosting, etc.)
   - Add plans for each category with pricing
   - Set up custom pages (Privacy, Terms, FAQ)

4. **Enable Social Login** (Optional)
   - Configure Google OAuth credentials
   - Configure Discord OAuth credentials
   - Configure GitHub OAuth credentials

### Managing Docker

```bash
# View running services
docker ps

# View logs
docker logs billing-panel-app
docker logs billing-panel-web

# Restart services
cd /opt/billing-panel && docker compose restart

# Stop all services
cd /opt/billing-panel && docker compose down
```

### Managing Database

```bash
# Access MySQL
docker exec -it billing-panel-db mysql -u root -psecret billing

# Backup database
docker exec billing-panel-db mysqldump -u root -psecret billing > backup.sql

# Restore database
docker exec -i billing-panel-db mysql -u root -psecret billing < backup.sql
```

## 🔧 Configuration

### Environment Variables

Edit `/opt/billing-panel/.env`:

```env
APP_NAME="Billing Panel"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://billing.example.com
APP_KEY=base64:xxxxxxxxxxx

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=billing
DB_USERNAME=root
DB_PASSWORD=secret

QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

### Setting Up Email (SMTP)

For transactional emails (invoices, notifications):

1. Get SMTP credentials from Mailtrap, SendGrid, or AWS SES
2. Update `.env` with SMTP details
3. Restart containers: `docker compose restart`

### Configuring Social Login

#### Google OAuth

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Create a new project
3. Enable Google+ API
4. Create OAuth 2.0 credentials (Web application)
5. Add authorized redirect URI: `https://billing.example.com/auth/callback/google`
6. Copy Client ID and Secret to admin panel

#### Discord OAuth

1. Go to [Discord Developer Portal](https://discord.com/developers/applications)
2. Create New Application
3. Enable OAuth2
4. Add redirect URL: `https://billing.example.com/auth/callback/discord`
5. Copy Client ID and Secret to admin panel

#### GitHub OAuth

1. Go to [GitHub Settings > Developer settings](https://github.com/settings/developers)
2. Create OAuth App
3. Set Authorization callback URL: `https://billing.example.com/auth/callback/github`
4. Copy Client ID and Secret to admin panel

## 🗂️ Project Structure

```
/opt/billing-panel/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Frontend & API controllers
│   │   ├── Middleware/         # Authentication & authorization
│   │   └── Kernel.php
│   ├── Models/
│   │   ├── User.php           # User with social login
│   │   ├── Invoice.php
│   │   ├── Order.php
│   │   ├── Plan.php
│   │   ├── ServiceCategory.php
│   │   └── Page.php
│   └── Notifications/
├── database/
│   ├── migrations/             # Database schema
│   └── factories/
├── resources/
│   └── views/                  # Blade templates
│       ├── index.blade.php     # Homepage
│       ├── shop/               # Shop pages
│       ├── checkout/           # Checkout flow
│       ├── dashboard/          # User dashboard
│       └── admin/              # Admin panel
├── routes/
│   ├── web.php                 # Frontend routes
│   └── api.php                 # API routes
├── docker-compose.yml
├── Dockerfile
├── Caddyfile
└── scripts/
    └── manage.sh               # Install/uninstall script
```

## 🛠️ Uninstallation

To completely remove Billing-Panel:

```bash
sudo bash /opt/billing-panel/scripts/manage.sh
# Choose option 2 (Uninstall)
# Confirm twice
```

This will:
- Stop all Docker containers
- Remove all data and volumes
- Delete the installation directory

## 📚 API Documentation

### Authentication Endpoints

```
POST   /login           - Login with email/password
POST   /register        - Create new account
POST   /logout          - Logout user
GET    /auth/google     - Google OAuth redirect
GET    /auth/discord    - Discord OAuth redirect
GET    /auth/github     - GitHub OAuth redirect
```

### Frontend Endpoints

```
GET    /                          - Homepage
GET    /shop                      - All services
GET    /shop/{category}           - Services by category
GET    /pages/{slug}              - Public pages
GET    /checkout/{plan}           - Checkout page
POST   /checkout/{plan}           - Create order
```

### Admin Endpoints

```
GET    /admin/pages               - List pages
POST   /admin/pages               - Create page
PUT    /admin/pages/{id}          - Update page
DELETE /admin/pages/{id}          - Delete page

GET    /admin/categories          - List categories
POST   /admin/categories          - Create category
PUT    /admin/categories/{id}     - Update category
DELETE /admin/categories/{id}     - Delete category

GET    /admin/plans               - List plans
POST   /admin/plans               - Create plan
PUT    /admin/plans/{id}          - Update plan
DELETE /admin/plans/{id}          - Delete plan
```

## 🐛 Troubleshooting

### Installation Issues

**Docker not starting:**
```bash
sudo systemctl start docker
sudo usermod -aG docker $USER
```

**Port 80/443 in use:**
```bash
sudo lsof -i :80
sudo lsof -i :443
# Kill any process using these ports
```

**Database connection failed:**
```bash
# Check database container
docker logs billing-panel-db

# Check connectivity
docker exec billing-panel-app ping db
```

### Runtime Issues

**Cannot access panel:**
- Ensure DNS is pointing to your server IP
- Check firewall: `sudo ufw allow 80,443/tcp`
- Verify Caddy: `docker logs billing-panel-web`

**Email not sending:**
- Check SMTP credentials in `.env`
- View queue logs: `docker logs billing-panel-worker`
- Test manually: `docker exec billing-panel-app php artisan mail:send`

**Database errors:**
- Check disk space: `df -h`
- Check memory: `free -h`
- View logs: `docker logs billing-panel-app`

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## 📄 License

This project is open source and available under the MIT License.

## 🆘 Support

For issues and questions:
- GitHub Issues: https://github.com/isthisvishal/Billing-Panel/issues
- Documentation: See README.md and QUICKSTART.md
- Community: Join our Discord server

## 🔐 Security

- Keep Docker updated
- Change default credentials
- Use strong database passwords
- Enable HTTPS (automatic via Caddy)
- Regularly backup your database
- Monitor logs for suspicious activity

## 📊 Performance Tips

1. **Database Optimization**
   - Add indexes for frequently queried columns
   - Regular backups

2. **Caching**
   - Redis enabled by default
   - Configure cache drivers in `.env`

3. **Scaling**
   - Use managed database services (AWS RDS, DigitalOcean)
   - Deploy multiple app instances behind load balancer
   - Use CDN for static assets

## 🎯 Roadmap

- [ ] Stripe payment integration
- [ ] PayPal payment integration
- [ ] Cryptocurrency payments
- [ ] Invoice management
- [ ] Automated billing cycles
- [ ] Client API for service provisioning
- [ ] Advanced reporting and analytics
- [ ] White-label options
- [ ] Mobile app

## 👨‍💻 Author

Created by [Vishal](https://github.com/isthisvishal)

---

**Made with ❤️ for hosting providers and SaaS businesses**
