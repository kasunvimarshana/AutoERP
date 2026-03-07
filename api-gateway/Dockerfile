FROM php:8.2-fpm-alpine

RUN apk add --no-cache curl libpng-dev libxml2-dev zip unzip git oniguruma-dev nginx supervisor

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd opcache sockets

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --optimize-autoloader --no-dev --no-interaction

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 8000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
