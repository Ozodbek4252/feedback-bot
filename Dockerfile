# syntax=docker/dockerfile:1

##
## ---- Frontend assets ----
##
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm install

COPY . .
RUN npm run build

##
## ---- Application image ----
##
FROM php:8.4-fpm-alpine AS app

WORKDIR /var/www/html

RUN apk add --no-cache nginx supervisor \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        sqlite-dev \
        libpng-dev \
        freetype-dev \
        libjpeg-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_sqlite \
        pdo_mysql \
        bcmath \
        intl \
        zip \
        gd \
        pcntl \
        exif \
    && apk add --no-cache icu-libs libzip sqlite-libs libpng freetype libjpeg-turbo \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-ansi

RUN mkdir -p database storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && touch database/database.sqlite \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache database

COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /run/nginx \
    && chown -R nginx:nginx /run/nginx /var/lib/nginx

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
