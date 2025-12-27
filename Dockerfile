



FROM php:8.4-fpm-bullseye

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libicu-dev libzip-dev libpq-dev libonig-dev libpng-dev \
    libjpeg-dev libfreetype6-dev curl zip ca-certificates openssl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql intl zip mbstring opcache gd bcmath

# Install redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create app user
ARG APP_USER=app
ARG APP_UID=1000
ARG APP_GID=1000
RUN groupadd -g ${APP_GID} ${APP_USER} || true \
  && useradd -m -u ${APP_UID} -g ${APP_GID} -s /bin/bash ${APP_USER} || true

WORKDIR /var/www

# Copy composer files and install dependencies
COPY composer.json composer.lock* /var/www/
RUN chown -R ${APP_USER}:${APP_USER} /var/www \
  && export COMPOSER_ALLOW_SUPERUSER=1 \
  && composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# Copy the rest of the application
COPY --chown=${APP_USER}:${APP_USER} . /var/www

# Ensure storage and cache directories exist and are writable
RUN mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions bootstrap/cache \
    && chown -R ${APP_USER}:${APP_USER} storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copy entrypoint script
COPY scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set PHP-FPM to listen on all interfaces
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

EXPOSE 9000

USER ${APP_USER}:${APP_USER}
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
