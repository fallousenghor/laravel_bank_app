FROM php:8.4-fpm

# Arguments définis dans docker-compose.yml
ARG user=laravel
ARG uid=1000

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    default-libmysqlclient-dev \
    zip \
    unzip \
    libpq-dev

# Nettoyer le cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd || true

# Obtenir la dernière version de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Créer un utilisateur système pour exécuter les commandes Composer et Artisan
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Configuration PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet
COPY . .

# Copier les permissions du projet
COPY --chown=$user:$user . .

# Fix git safe directory and installer dependencies
# Add safe.directory so composer/git won't fail with 'detected dubious ownership'
RUN git config --global --add safe.directory /var/www/html || true

# Installer les dépendances du projet
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Créer les répertoires nécessaires
RUN mkdir -p /var/www/html/storage/logs \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/cache \
    /var/www/html/bootstrap/cache

# Définir les permissions du storage et bootstrap/cache
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# S'assurer que le processus PHP peut écrire dans ces répertoires
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Changer vers l'utilisateur www-data pour php-fpm
# Copier le script d'entrypoint qui exécutera migrations / seeders au démarrage
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh && chown www-data:www-data /usr/local/bin/docker-entrypoint.sh

# Changer vers l'utilisateur www-data pour php-fpm
USER www-data

# Exécuter le script d'entrypoint (s'exécutera avec l'utilisateur www-data)
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Start the Laravel built-in server so Render can detect the HTTP port
EXPOSE 8000
CMD ["sh", "-lc", "php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
