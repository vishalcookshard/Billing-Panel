

# Use a PHP image with OpenSSL 1.1.1 compatibility
FROM php:8.2-fpm-bullseye

# Install system dependencies and OpenSSL 1.1 compatibility
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    curl \
    zip \
    ca-certificates \
    openssl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    intl \
    zip \
    mbstring \
    opcache

# Enable OpCache
RUN docker-php-ext-enable opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files first
COPY composer.json composer.lock* ./







# Copy the entire Laravel application (including artisan) before composer install
COPY . /var/www

# Create required Laravel runtime directories and set permissions before composer install
RUN mkdir -p /var/www/bootstrap/cache \
    /var/www/storage \
    /var/www/storage/framework \
    /var/www/storage/logs \
    && chown -R www-data:www-data /var/www/bootstrap/cache /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache /var/www/storage

# Install composer dependencies WITHOUT running scripts
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --no-scripts

# Ensure directories exist with correct permissions
RUN mkdir -p storage/logs storage/framework/{views,cache} bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Make artisan executable
RUN chmod +x artisan 2>/dev/null || true

# Set PHP-FPM to listen on all interfaces
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

# Create robust entrypoint that initializes environment, permissions, app key, then runs composer and artisan
RUN echo '#!/usr/bin/env bash\nset -euo pipefail\n\n# 1. Prepare directories\nmkdir -p /var/www/storage/logs /var/www/storage/framework/{views,cache} /var/www/bootstrap/cache\nchown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache\nchmod -R 775 /var/www/storage /var/www/bootstrap/cache\n\n# 2. Prepare .env if missing\nif [ ! -f /var/www/.env ]; then\n  echo "No .env found, copying from .env.example..."\n  cp /var/www/.env.example /var/www/.env\nfi\n\n# 3. Generate APP_KEY if missing\nif ! grep -q "^APP_KEY=" /var/www/.env || grep -q "^APP_KEY=$" /var/www/.env; then\n  echo "Generating APP_KEY..."\n  php /var/www/artisan key:generate --ansi\nfi\n\n# 4. Run composer install if vendor/autoload.php missing\nif [ ! -f /var/www/vendor/autoload.php ]; then\n  echo "Running composer install..."\n  composer install --no-interaction --no-scripts --optimize-autoloader\nfi\n\n# 5. Run artisan package:discover\nphp /var/www/artisan package:discover --ansi\n\n# 6. Run migrations (with DB wait/retry)\nif [ "${APP_ENV:-}" != "testing" ]; then\n  echo "Waiting for database and running migrations..."\n  MAX_TRIES=30\n  TRY=0\n  until php /var/www/artisan migrate --force; do\n    TRY=$((TRY+1))\n    if [ "$TRY" -ge "$MAX_TRIES" ]; then\n      echo "ERROR: Migrations failed after ${MAX_TRIES} attempts." >&2\n      exit 1\n    fi\n    echo "Migration attempt ${TRY}/${MAX_TRIES} failed; retrying in 2s..." >&2\n    sleep 2\n  done\nfi\n\nexec "$@"' > /usr/local/bin/docker-entrypoint.sh && chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD php -v || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
