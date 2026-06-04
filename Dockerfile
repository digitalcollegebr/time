# syntax=docker/dockerfile:1
#
# Build-from-source image for the "Time" whitelabel (Digital College fork of Leantime).
# Unlike .docker/Dockerfile (which downloads the official Leantime release), this builds
# the image from THIS repository so the rebrand (logo, name, pt-BR) is included.
#
# Stages:
#   php-ext  -> compiles the PHP extensions
#   vendor   -> installs composer dependencies (no-dev)
#   assets   -> compiles JS/CSS with Laravel Mix (generates public/dist)
#   final    -> nginx + php-fpm runtime serving the built app

############################
# Stage 1: PHP extensions
############################
FROM php:8.3-fpm-alpine AS php-ext

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    gcc g++ make \
    openssl-dev libxml2-dev oniguruma-dev openldap-dev \
    zstd-dev libzip-dev freetype-dev libpng-dev libjpeg-turbo-dev postgresql-dev

RUN set -ex; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install mysqli pdo_mysql pdo_pgsql bcmath mbstring exif pcntl opcache ldap zip gd && \
    pecl install redis && docker-php-ext-enable redis && \
    rm -rf /tmp/* /var/cache/apk/*

############################
# Stage 2: Composer deps
############################
FROM php:8.3-fpm-alpine AS vendor

COPY --from=php-ext /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=php-ext /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# runtime libs so the extensions load while composer dumps the optimized autoloader
RUN apk add --no-cache git unzip libzip freetype libpng libjpeg-turbo openldap libpq

WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

############################
# Stage 3: Frontend assets
############################
FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
# Produces public/dist/* and the Tailwind blocklist
RUN npx mix --production && node generateBlocklist.mjs

############################
# Stage 4: Runtime
############################
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    tini nginx supervisor mysql-client openssl curl \
    freetype libpng libjpeg-turbo libzip openldap libpq \
    zstd-libs icu-libs && \
    rm -rf /var/cache/apk/* /tmp/*

COPY --from=php-ext /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=php-ext /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

ARG PUID=1000
ARG PGID=1000
WORKDIR /var/www/html

# Application source (this fork) + built artifacts from the previous stages
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/dist ./public/dist

# Runtime config (lives under .docker in this repo)
COPY .docker/config/custom.ini /usr/local/etc/php/conf.d/
COPY .docker/config/nginx.conf /etc/nginx/nginx.conf
COPY .docker/config/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY .docker/config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY --chmod=0755 .docker/start.sh /start.sh

RUN set -ex; \
    deluser www-data; \
    addgroup -g ${PGID} www-data; \
    adduser -u ${PUID} -G www-data -h /home/www-data -s /bin/sh -D www-data; \
    mkdir -p /var/www/html/userfiles \
             /var/www/html/public/userfiles \
             /var/www/html/bootstrap/cache \
             /var/www/html/storage/logs \
             /var/www/html/storage/framework/cache \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/app/Plugins \
             /run /var/log/nginx /var/lib/nginx; \
    chown -R www-data:www-data /var/www/html /run /var/log/nginx /var/lib/nginx; \
    chmod -R 775 /var/www/html/userfiles \
                 /var/www/html/public/userfiles \
                 /var/www/html/bootstrap/cache \
                 /var/www/html/storage/logs \
                 /var/www/html/storage/framework \
                 /var/www/html/app/Plugins

USER www-data

HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
    CMD curl -f http://localhost:8080 || exit 1

EXPOSE 8080
ENTRYPOINT ["/sbin/tini", "--", "/start.sh"]
