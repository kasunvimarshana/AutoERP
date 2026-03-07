# ============================================================
#  Inventory Service – Laravel 10
# ============================================================

# ---- base -------------------------------------------------------
FROM php:8.2-fpm-alpine AS base

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    postgresql-dev \
    zip \
    unzip \
    linux-headers \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install \
      pdo \
      pdo_pgsql \
      mbstring \
      xml \
      bcmath \
      gd \
      sockets \
      pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---- development ------------------------------------------------
FROM base AS development

ENV APP_ENV=local \
    APP_DEBUG=true

COPY composer.json composer.lock* ./

RUN composer install --no-scripts --no-autoloader --ansi

COPY . .

RUN composer dump-autoload --optimize \
 && php artisan package:discover --ansi || true

EXPOSE 8002

CMD ["php", "-S", "0.0.0.0:8002", "-t", "public"]

# ---- production -------------------------------------------------
FROM base AS production

ENV APP_ENV=production \
    APP_DEBUG=false

COPY composer.json composer.lock* ./

RUN composer install --no-dev --no-scripts --no-autoloader --ansi

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative \
 && php artisan config:cache \
 && php artisan route:cache \
 && php artisan event:cache

EXPOSE 8002

CMD ["php-fpm"]
