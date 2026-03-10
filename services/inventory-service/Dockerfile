FROM php:8.3-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    unzip \
    curl \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN touch database/database.sqlite \
    && php artisan migrate --force \
    && php artisan config:cache \
    && php artisan route:cache

EXPOSE 8004

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8004"]
