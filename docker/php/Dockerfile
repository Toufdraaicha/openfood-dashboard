# docker/php/Dockerfile
FROM ghcr.io/frankenphp/frankenphp:latest

# Installer les dépendances PHP nécessaires pour Symfony 8
RUN apk add --no-cache \
    php8.5-cli php8.5-mbstring php8.5-openssl php8.5-pdo \
    php8.5-pdo_mysql php8.5-curl php8.5-intl php8.5-xml \
    php8.5-bcmath php8.5-zip php8.5-gd php8.5-soap php8.5-xsl \
    bash git unzip curl

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Créer le répertoire de l'application
WORKDIR /app

# Copier le projet
COPY . /app

# Installer les dépendances Composer
RUN composer install --no-interaction --optimize-autoloader

# Exposer le port pour FrankenPHP
EXPOSE 8000

# Commande par défaut
CMD ["frankenphp", "-S", "0.0.0.0:8000", "-t", "public"]
