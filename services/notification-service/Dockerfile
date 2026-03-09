FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    git curl libpng-dev libxml2-dev zip unzip oniguruma-dev rabbitmq-c-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && pecl install redis amqp \
    && docker-php-ext-enable redis amqp

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

RUN composer install --no-dev --optimize-autoloader

EXPOSE 9000
CMD ["php-fpm"]
