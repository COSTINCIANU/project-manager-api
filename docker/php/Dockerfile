# Image de base PHP 8.2 avec FPM
FROM php:8.2-fpm

# Installation des extensions PHP nécessaires pour Symfony
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définition du dossier de travail
WORKDIR /var/www/html

# Copie des fichiers du projet
COPY . .

# Installation des dépendances sans les scripts Symfony
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-scripts

# Permissions sur le dossier var/
RUN chown -R www-data:www-data /var/www/html/var

# Exposition du port PHP-FPM
EXPOSE 9000

# Copie du script d'initialisation
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Démarrage via le script d'initialisation
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
