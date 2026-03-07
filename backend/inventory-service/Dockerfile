FROM php:8.2-fpm-alpine
RUN apk add --no-cache nginx supervisor curl zip unzip git oniguruma-dev libxml2-dev rabbitmq-c-dev autoconf g++ make
RUN docker-php-ext-install pdo_mysql mbstring xml opcache
RUN pecl install redis amqp && docker-php-ext-enable redis amqp
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-interaction --optimize-autoloader --no-dev 2>/dev/null || true
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN mkdir -p /var/www/html/storage/logs /var/www/html/bootstrap/cache && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
EXPOSE 8000
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
