


# Use official PHP 8.2 FPM image (includes php-fpm and all extensions)
FROM php:8.2-fpm-bullseye

# Install system dependencies and OpenSSL 1.1 compatibility
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    curl \
    zip \
    ca-certificates \
    openssl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    intl \
    zip \
    mbstring \
    opcache \
    gd \
    bcmath

# Enable OpCache
RUN docker-php-ext-enable opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Create a dedicated non-root user for the application
ARG APP_USER=app
ARG APP_UID=1000
ARG APP_GID=1000
RUN groupadd -g ${APP_GID} ${APP_USER} || true \
  && useradd -m -u ${APP_UID} -g ${APP_GID} -s /bin/bash ${APP_USER} || true

# Set working directory early
WORKDIR /var/www

# Copy composer files and install deps (production)
COPY composer.json composer.lock* /var/www/
RUN chown -R ${APP_USER}:${APP_USER} /var/www \
  && export COMPOSER_ALLOW_SUPERUSER=1 \
  && composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# Copy the rest of the application as app user
COPY --chown=${APP_USER}:${APP_USER} . /var/www

# Copy entrypoint script
COPY scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set PHP-FPM to listen on all interfaces
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

# Expose port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD php -v || exit 1

# Run as non-root user
USER ${APP_USER}:${APP_USER}

# Entrypoint only (php-fpm is run by entrypoint script)
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
