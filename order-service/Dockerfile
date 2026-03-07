# ─── Build stage ─────────────────────────────────────────────────────────────
FROM php:8.2-fpm AS base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    git \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    supervisor \
    unzip \
    zip \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        xml \
        zip \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy dependency files first for better layer caching
COPY composer.json composer.lock* ./

# Install PHP dependencies (no dev in production)
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

# Copy application code
COPY . .

# Run post-install scripts
RUN composer dump-autoload --optimize

# Create necessary directories and set permissions
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 755 storage bootstrap/cache

# Copy supervisor config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Configure PHP-FPM to listen on port 8000 via built-in server for simplicity
EXPOSE 8000

# Use supervisor to run both php-fpm and the saga consumer
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
