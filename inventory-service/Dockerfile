FROM php:8.2-fpm AS base

RUN apt-get update && apt-get install -y \
    curl \
    git \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    supervisor \
    unzip \
    zip \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        xml \
        zip \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --optimize

RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 755 storage bootstrap/cache

COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 8001

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
