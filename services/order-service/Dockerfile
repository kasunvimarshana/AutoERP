# ─────────────────────────────────────────────────────────────────────────────
# order-service – Multi-stage Docker build
# ─────────────────────────────────────────────────────────────────────────────

FROM composer:2.7 AS composer_stage

WORKDIR /app

COPY composer.json composer.lock* ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --ignore-platform-reqs \
        --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

FROM php:8.2-fpm-alpine AS production

LABEL maintainer="KV-SAAS" \
      service="order-service"

RUN apk add --no-cache \
        nginx \
        supervisor \
        curl \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        freetype-dev \
        zip \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        zip \
        intl \
        mbstring \
        opcache \
        gd \
        pcntl \
        sockets \
    && pecl install redis-6.0.2 \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

COPY docker/php.ini           /usr/local/etc/php/php.ini
COPY docker/www.conf          /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx.conf        /etc/nginx/nginx.conf
COPY docker/supervisord.conf  /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

COPY --from=composer_stage /app .

RUN chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
    && chmod -R 775 \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache

EXPOSE 8005

HEALTHCHECK --interval=30s --timeout=10s --retries=5 \
    CMD curl -f http://localhost:8005/api/health || exit 1

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
