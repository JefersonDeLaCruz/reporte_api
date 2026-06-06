FROM php:8.4-fpm

# Argumentos para UID/GID dinámicos
ARG UID=1000
ARG GID=1000

# Instalar extensiones necesarias (mínimo para API)
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libonig-dev libxml2-dev zip curl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instalar Composer globalmente
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Crear usuario con UID/GID del host
RUN groupadd -g ${GID} laravel && \
    useradd -u ${UID} -g ${GID} -m laravel

# Dar permisos al webroot
RUN chown -R laravel:laravel /var/www/html

# Cambiar a usuario no root
USER laravel

WORKDIR /var/www/html

EXPOSE 9000