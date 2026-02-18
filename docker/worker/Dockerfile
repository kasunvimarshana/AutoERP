FROM php:8.2-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    pcre-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pgsql pcntl

# Install Redis extension
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY backend/ .

# Install dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Run queue worker
CMD ["php", "artisan", "queue:work", "--tries=3", "--timeout=90"]
