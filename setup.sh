#!/bin/bash

# AutoERP Installation Script
# This script sets up both backend and frontend

set -e

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║              AutoERP Installation Script                     ║"
echo "║  Production-ready SaaS for Vehicle Service Centers           ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# Check prerequisites
echo "→ Checking prerequisites..."
command -v php >/dev/null 2>&1 || { echo "✗ PHP is required but not installed. Aborting." >&2; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "✗ Composer is required but not installed. Aborting." >&2; exit 1; }
command -v node >/dev/null 2>&1 || { echo "✗ Node.js is required but not installed. Aborting." >&2; exit 1; }
command -v npm >/dev/null 2>&1 || { echo "✗ npm is required but not installed. Aborting." >&2; exit 1; }

echo "✓ All prerequisites found"
echo ""

# Backend Setup
echo "→ Setting up Backend (Laravel)..."
cd backend

if [ ! -f ".env" ]; then
    echo "  • Copying .env.example to .env"
    cp .env.example .env
fi

echo "  • Installing Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "  • Generating application key..."
php artisan key:generate --force

echo "  • Running database migrations..."
php artisan migrate --force

echo "  • Publishing vendor assets..."
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="permission-migrations" --force
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations" --force

echo "  • Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✓ Backend setup completed"
echo ""

cd ..

# Frontend Setup
echo "→ Setting up Frontend (Vue.js)..."
cd frontend

echo "  • Installing npm dependencies..."
npm install

if [ ! -f ".env" ]; then
    echo "  • Creating .env file..."
    echo "VITE_API_URL=http://localhost:8000/api/v1" > .env
fi

echo "  • Building frontend..."
npm run build

echo "✓ Frontend setup completed"
echo ""

cd ..

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║           Installation Completed Successfully!               ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
echo "Next steps:"
echo ""
echo "1. Configure your database in backend/.env"
echo "2. Run migrations: cd backend && php artisan migrate"
echo "3. Start the backend: cd backend && php artisan serve"
echo "4. Start the frontend: cd frontend && npm run dev"
echo ""
echo "Visit http://localhost:5173 to access the application"
echo ""
