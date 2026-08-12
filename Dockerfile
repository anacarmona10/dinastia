# Etapa 1: instala dependencias PHP con Composer
FROM composer:2 AS dependencies

WORKDIR /app/backend

COPY backend/composer.json backend/composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader


# Etapa 2: servidor web PHP + Apache
FROM php:8.3-apache

# Instala la extensión PDO para PostgreSQL (Neon)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Configuración propia de Apache
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Copia frontend, backend y el resto de la aplicación
COPY . .

# Copia las dependencias instaladas desde la primera etapa
COPY --from=dependencies /app/backend/vendor ./backend/vendor

# Apache debe poder leer/escribir imágenes y sesiones
RUN mkdir -p /var/lib/php/sessions \
    && chown -R www-data:www-data /var/www/html/frontend/imagenes /var/lib/php/sessions

EXPOSE 80