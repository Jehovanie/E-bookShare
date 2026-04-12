# ─────────────────────────────────────────────────────────────
# Stage 1 — Assets builder (Node.js + Tailwind CSS)
# ─────────────────────────────────────────────────────────────
FROM node:20-alpine AS assets

WORKDIR /app

COPY package*.json ./
RUN npm ci --prefer-offline

COPY webpack.config.js postcss.config.js ./
COPY assets/ assets/

RUN npm run build

# ─────────────────────────────────────────────────────────────
# Stage 2 — PHP application (Apache)
# ─────────────────────────────────────────────────────────────
FROM php:8.2-apache AS app

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libicu-dev \
        libzip-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        intl \
        zip \
        opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# PHP production settings
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

# Apache virtual host → public/
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# Composer deps (no dev, optimized autoloader)
COPY composer.json composer.lock symfony.lock* ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --optimize-autoloader \
        --prefer-dist

# Application source
COPY . .

# Compiled assets from Stage 1
COPY --from=assets /app/public/build public/build/

# Create writable directories, install JS vendor packages, compile assets, warm up cache
RUN mkdir -p var/cache var/log public/books public/assets \
    && chown -R www-data:www-data var public/books public/assets \
    && chmod -R 775 var public/books public/assets \
    && composer dump-env prod \
    && php bin/console importmap:install \
    && su www-data -s /bin/sh -c "APP_ENV=prod php bin/console asset-map:compile --no-debug" \
    && su www-data -s /bin/sh -c "APP_ENV=prod php bin/console cache:warmup --no-debug" \
    && chown -R www-data:www-data var public/assets \
    && chmod -R 775 var public/assets

# Expose HTTP
EXPOSE 80

ENV APP_ENV=prod
