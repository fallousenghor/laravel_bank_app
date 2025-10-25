FROM php:8.2-fpm

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
    zip \
    unzip \
    libpq-dev

# Nettoyer le cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Obtenir la dernière version de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Créer un utilisateur système pour exécuter les commandes Composer et Artisan
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet
COPY . .

# Copier les permissions du projet
COPY --chown=$user:$user . .

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
USER www-data

# Exposer le port 9000 et démarrer php-fpm
EXPOSE 9000
CMD ["php-fpm"]
USER $user

# Exposer le port 9000 et démarrer php-fpm
EXPOSE 9000
CMD ["php-fpm"]
