#!/bin/bash

# AutoERP - Quick Setup Script
# This script sets up both backend and frontend for development

set -e

echo "=========================================="
echo "AutoERP - Setup Script"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check prerequisites
echo "Checking prerequisites..."

# Check PHP
if ! command -v php &> /dev/null; then
    echo -e "${RED}Error: PHP is not installed${NC}"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "${GREEN}✓ PHP $PHP_VERSION installed${NC}"

# Check Composer
if ! command -v composer &> /dev/null; then
    echo -e "${RED}Error: Composer is not installed${NC}"
    exit 1
fi

COMPOSER_VERSION=$(composer --version | cut -d ' ' -f 3)
echo -e "${GREEN}✓ Composer $COMPOSER_VERSION installed${NC}"

# Check Node.js
if ! command -v node &> /dev/null; then
    echo -e "${RED}Error: Node.js is not installed${NC}"
    exit 1
fi

NODE_VERSION=$(node --version)
echo -e "${GREEN}✓ Node.js $NODE_VERSION installed${NC}"

# Check npm
if ! command -v npm &> /dev/null; then
    echo -e "${RED}Error: npm is not installed${NC}"
    exit 1
fi

NPM_VERSION=$(npm --version)
echo -e "${GREEN}✓ npm $NPM_VERSION installed${NC}"

echo ""
echo "=========================================="
echo "Setting up Backend (Laravel)"
echo "=========================================="
echo ""

cd backend

# Install Composer dependencies
echo "Installing Composer dependencies..."
composer install

# Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.example .env
    echo -e "${GREEN}✓ .env file created${NC}"
else
    echo -e "${YELLOW}! .env file already exists${NC}"
fi

# Generate application key
echo "Generating application key..."
php artisan key:generate

# Publish vendor packages
echo "Publishing vendor packages..."
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --force
php artisan vendor:publish --provider="Stancl\Tenancy\TenancyServiceProvider" --tag=migrations --force
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --force
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --force

echo -e "${GREEN}✓ Backend setup complete${NC}"
echo ""
echo "=========================================="
echo "Setting up Frontend (Vue.js)"
echo "=========================================="
echo ""

cd ../frontend

# Install npm dependencies
echo "Installing npm dependencies..."
npm install

# Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.example .env
    echo -e "${GREEN}✓ .env file created${NC}"
else
    echo -e "${YELLOW}! .env file already exists${NC}"
fi

cd ..

echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo ""
echo "1. Configure your database in backend/.env:"
echo "   DB_CONNECTION=mysql"
echo "   DB_HOST=127.0.0.1"
echo "   DB_PORT=3306"
echo "   DB_DATABASE=autoerp"
echo "   DB_USERNAME=your_username"
echo "   DB_PASSWORD=your_password"
echo ""
echo "2. Run database migrations:"
echo "   cd backend"
echo "   php artisan migrate"
echo "   php artisan db:seed"
echo ""
echo "3. Start the backend server:"
echo "   cd backend"
echo "   php artisan serve"
echo "   (Runs on http://localhost:8000)"
echo ""
echo "4. Start the frontend development server:"
echo "   cd frontend"
echo "   npm run dev"
echo "   (Runs on http://localhost:5173)"
echo ""
echo "5. Access the application:"
echo "   Frontend: http://localhost:5173"
echo "   Backend API: http://localhost:8000/api"
echo "   Swagger Docs: http://localhost:8000/api/documentation"
echo ""
echo "=========================================="
echo "Optional: Docker Setup"
echo "=========================================="
echo ""
echo "To use Docker instead:"
echo "   docker-compose up -d"
echo "   docker-compose exec backend php artisan migrate"
echo ""
echo "=========================================="
