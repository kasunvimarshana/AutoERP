#!/bin/bash

# AutoERP Installation Script
# This script automates the installation and setup process

set -e  # Exit on error

echo "========================================="
echo "  AutoERP Installation Script"
echo "========================================="
echo ""

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "Error: PHP is not installed"
    exit 1
fi

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    echo "Error: Composer is not installed"
    exit 1
fi

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "Error: Node.js is not installed"
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "Error: npm is not installed"
    exit 1
fi

echo "✓ All prerequisites are installed"
echo ""

# Install PHP dependencies
echo "Installing PHP dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "✓ PHP dependencies installed"
echo ""

# Install Node.js dependencies
echo "Installing Node.js dependencies..."
npm install

echo "✓ Node.js dependencies installed"
echo ""

# Setup environment file
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.example .env
    echo "✓ .env file created"
else
    echo "✓ .env file already exists"
fi
echo ""

# Generate application key
echo "Generating application key..."
php artisan key:generate --force

echo "✓ Application key generated"
echo ""

# Create storage link
echo "Creating storage link..."
php artisan storage:link || true

echo "✓ Storage link created"
echo ""

# Set permissions
echo "Setting file permissions..."
chmod -R 775 storage bootstrap/cache

echo "✓ Permissions set"
echo ""

# Ask if user wants to run migrations
read -p "Do you want to run database migrations? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Running migrations..."
    php artisan migrate --force
    echo "✓ Migrations completed"
    echo ""
fi

# Ask if user wants to seed database
read -p "Do you want to seed the database with sample data? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Seeding database..."
    php artisan db:seed --force
    echo "✓ Database seeded"
    echo ""
fi

# Publish vendor assets
echo "Publishing vendor assets..."
php artisan vendor:publish --tag=laravel-assets --force || true

echo "✓ Vendor assets published"
echo ""

# Build frontend assets
read -p "Do you want to build frontend assets now? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Building frontend assets..."
    npm run build
    echo "✓ Frontend assets built"
else
    echo "Skipping frontend build. Run 'npm run dev' or 'npm run build' later."
fi
echo ""

# Clear and cache configuration
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✓ Application optimized"
echo ""

echo "========================================="
echo "  Installation Complete!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Configure your database in .env file"
echo "2. Run 'php artisan migrate' if you haven't already"
echo "3. Start the development server:"
echo "   - Backend: php artisan serve"
echo "   - Frontend: npm run dev"
echo ""
echo "4. Visit http://localhost:8000 to see the application"
echo ""
echo "For more information, see:"
echo "- SETUP.md for detailed setup instructions"
echo "- ARCHITECTURE.md for system architecture"
echo "- README.md for general information"
echo ""
echo "API Documentation will be available at:"
echo "http://localhost:8000/api/documentation"
echo ""

