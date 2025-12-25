

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




# Create a fresh Laravel application skeleton before merging project files
# This ensures the framework, core service providers and expected structure exist
RUN composer create-project laravel/laravel . --no-interaction --prefer-dist --stability=stable

# Copy our application files into the Laravel skeleton (preserve vendor)
# Selective copy to avoid overwriting Laravel core files like vendor/ and node_modules/
COPY app/ app/
COPY bootstrap/ bootstrap/
COPY config/ config/
COPY database/ database/
COPY resources/ resources/
COPY routes/ routes/
COPY public/ public/
COPY composer.json composer.json
# Do not copy composer.lock (it may not exist)
COPY .env.example .env.example
COPY scripts/ scripts/
COPY Dockerfile Dockerfile
COPY vite.config.js vite.config.js

# Install composer dependencies (merge project's composer requirements)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

# Ensure directories exist with correct permissions
RUN mkdir -p storage/logs storage/framework/{views,cache} bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Make artisan executable
RUN chmod +x artisan 2>/dev/null || true

# Set PHP-FPM to listen on all interfaces
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

# Create robust entrypoint that waits for DB and fails loudly on migration errors
RUN echo '#!/usr/bin/env bash\nset -euo pipefail\n\n# Ensure directories exist and permissions are correct\nmkdir -p /var/www/storage/logs /var/www/storage/framework/{views,cache} /var/www/bootstrap/cache\nchown -R www-data:www-data /var/www\nchmod -R 775 /var/www/storage /var/www/bootstrap/cache\n\n# If APP_ENV is not testing, attempt to run migrations with retries\nif [ "${APP_ENV:-}" != "testing" ]; then\n  echo "Waiting for database and running migrations..."\n  MAX_TRIES=30\n  TRY=0\n  until php artisan migrate --force; do\n    TRY=$((TRY+1))\n    if [ "$TRY" -ge "$MAX_TRIES" ]; then\n      echo "ERROR: Migrations failed after ${MAX_TRIES} attempts." >&2\n      exit 1\n    fi\n    echo "Migration attempt ${TRY}/${MAX_TRIES} failed; retrying in 2s..." >&2\n    sleep 2\n  done\nfi\n\nexec "$@"' > /usr/local/bin/docker-entrypoint.sh && chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD php -v || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
