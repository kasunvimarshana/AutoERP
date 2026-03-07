# ─────────────────────────────────────────────────────────────────────────────
# Stage 1 – Composer dependencies
# ─────────────────────────────────────────────────────────────────────────────
FROM composer:2.7 AS vendor

WORKDIR /app

COPY composer.json composer.lock* ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --prefer-dist \
        --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --no-dev --classmap-authoritative

# ─────────────────────────────────────────────────────────────────────────────
# Stage 2 – Production image
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.2-fpm-alpine AS production

LABEL maintainer="laravel-inventory"
LABEL service="order-service"

# ── System dependencies ───────────────────────────────────────────────────────
RUN apk add --no-cache \
        bash \
        curl \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev \
        icu-dev \
        oniguruma-dev \
        libzip-dev \
        zip \
        unzip \
        git \
        supervisor

# ── PHP extensions ────────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        sockets

# ── Redis extension (via PECL) ────────────────────────────────────────────────
RUN pecl install redis \
    && docker-php-ext-enable redis

# ── OPcache tuning ────────────────────────────────────────────────────────────
RUN { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.save_comments=1'; \
        echo 'opcache.fast_shutdown=1'; \
    } >> /usr/local/etc/php/conf.d/opcache.ini

# ── Application ───────────────────────────────────────────────────────────────
WORKDIR /var/www/html

COPY --from=vendor /app/vendor        ./vendor
COPY --from=vendor /app               .

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# ── Supervisor ────────────────────────────────────────────────────────────────
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 9000

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# ─────────────────────────────────────────────────────────────────────────────
# Stage 3 – Development / testing image (extends production)
# ─────────────────────────────────────────────────────────────────────────────
FROM production AS development

RUN apk add --no-cache nodejs npm

COPY --from=vendor /app/vendor ./vendor

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && { \
        echo 'xdebug.mode=coverage,debug'; \
        echo 'xdebug.start_with_request=yes'; \
        echo 'xdebug.client_host=host.docker.internal'; \
        echo 'xdebug.client_port=9003'; \
    } >> /usr/local/etc/php/conf.d/xdebug.ini
