FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev \
    zip unzip && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies (production)
RUN composer install --no-dev --optimize-autoloader

# Setup storage permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Generate app key if not set, run migrations, seed, start server
CMD ["/bin/sh", "-c", "\
  php artisan key:generate --force && \
  php artisan migrate --force && \
  php artisan db:seed --force && \
  php artisan passport:keys --force && \
  php artisan passport:client --personal --name='SaaS API' --no-interaction && \
  php artisan serve --host=0.0.0.0 --port=8000 \
"]
