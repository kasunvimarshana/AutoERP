FROM php:8.3-fpm-alpine

LABEL maintainer="SaaS Platform Team"

# System dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    rabbitmq-c-dev \
    unzip \
    zip

# PHP extensions
RUN pecl install amqp redis && \
    docker-php-ext-enable amqp redis && \
    docker-php-ext-install \
        bcmath \
        gd \
        mbstring \
        pdo \
        pdo_pgsql \
        pcntl \
        zip

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY . .

# Finalise composer autoloader
RUN composer dump-autoload --optimize

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
