###
# Multi-stage Dockerfile for Laravel 10
# - builder stage installs system deps & Composer dependencies
# - production stage runs PHP-FPM as non-root (www-data)
# Notes:
# - Keep builds reproducible by copying composer files first to leverage cache
# - Do not run environment-specific artisan caching at build time (can be done at deploy)
###

FROM php:8.3-fpm AS builder

# Arguments
ARG USER=www-data
ARG UID=1000

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/composer

WORKDIR /var/www/html

# Install system dependencies required for Laravel and common extensions
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libpq-dev \
    ca-certificates \
 && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql pdo_pgsql mbstring zip exif pcntl bcmath intl opcache

# Install redis extension (optional, many apps use it)
RUN pecl install redis && docker-php-ext-enable redis || true

# Install Composer (use official composer image binary)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy composer files first to leverage Docker layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies (no dev, optimized autoloader)
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts --no-progress --no-plugins

# Copy application code
COPY . .

# Ensure storage and cache directories exist and have correct permissions
RUN mkdir -p storage/framework storage/logs bootstrap/cache \
 && chown -R ${USER}:${USER} storage bootstrap/cache || true

# Optimize autoloader after the full source is copied
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative --no-interaction


FROM php:8.3-fpm AS production

WORKDIR /var/www/html

# Install system dependencies and PHP extensions required at runtime
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
     libzip-dev \
     libpng-dev \
     libjpeg-dev \
     libfreetype6-dev \
     libonig-dev \
     libxml2-dev \
     libicu-dev \
     libpq-dev \
     ca-certificates \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql pdo_pgsql mbstring zip exif bcmath intl opcache \
 && pecl install redis && docker-php-ext-enable redis || true \
 && rm -rf /var/lib/apt/lists/*

# Copy the built application from the builder stage
COPY --from=builder /var/www/html /var/www/html

# Set correct permissions for runtime (best effort)
RUN usermod -u 1000 www-data || true \
 && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

USER www-data

EXPOSE 9000

# Copy entrypoint and make executable (entrypoint runs migrations/passport install when enabled)
COPY --chown=www-data:www-data entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Use entrypoint to allow optional startup tasks, then start php-fpm
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
