

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



# Copy application files (overwriting stub files with your code)
COPY . /var/www

# Install composer dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    2>&1

# Create necessary directories with proper permissions
RUN mkdir -p storage/logs \
    storage/framework/views \
    storage/framework/cache \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Make artisan executable
RUN chmod +x artisan 2>/dev/null || true

# Set PHP-FPM to listen on all interfaces
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

# Create entrypoint script
RUN echo '#!/bin/bash\n\
set -e\n\
# Ensure directories exist with correct permissions\n\
mkdir -p storage/logs storage/framework/{views,cache} bootstrap/cache\n\
chown -R www-data:www-data /var/www\n\
chmod -R 775 storage bootstrap/cache\n\
# Run migrations if APP_ENV is not testing\n\
if [ "$APP_ENV" != "testing" ]; then\n\
  echo "Running migrations..."\n\
  php artisan migrate --force 2>/dev/null || true\n\
fi\n\
exec "$@"' > /usr/local/bin/docker-entrypoint.sh && chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD php -v || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
