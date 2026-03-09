FROM php:8.2-fpm-alpine
WORKDIR /var/www/html
RUN apk add --no-cache nginx supervisor curl git zip unzip bash mysql-client libpng-dev libzip-dev icu-dev oniguruma-dev rabbitmq-c-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql mbstring pcntl bcmath zip intl opcache \
    && pecl install amqp redis && docker-php-ext-enable amqp redis
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 CMD curl -f http://localhost/health || exit 1
EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
