# Quick Start Guide - Vue.js Frontend

This guide will help you get the Vue.js frontend up and running quickly.

## Prerequisites

- PHP 8.3+
- Composer 2.x
- Node.js 18+ & npm
- MySQL 8+ or PostgreSQL 13+

## Installation Steps

### 1. Clone and Install Dependencies

```bash
# Clone the repository
git clone https://github.com/kasunvimarshana/ModularSaaS-LaravelVue.git
cd ModularSaaS-LaravelVue

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Configure Database

Edit `.env` and set your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=modular_saas
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Run Migrations and Seeders

```bash
# Run migrations
php artisan migrate

# Seed roles and permissions (important for authentication)
php artisan db:seed --class=Modules\\Auth\\Database\\Seeders\\AuthDatabaseSeeder
```

### 5. Install Laravel Sanctum

```bash
php artisan install:api
```

### 6. Build Frontend Assets

```bash
# Development build (with hot-reload)
npm run dev

# Production build
npm run build
```

### 7. Start the Application

```bash
# Start Laravel development server
php artisan serve

# In a separate terminal, start Vite (for development)
npm run dev

# Or use the convenient command that runs both:
composer dev
```

### 8. Access the Application

Open your browser and navigate to:
- Frontend: http://localhost:8000
- API: http://localhost:8000/api/v1

## Default Credentials

After seeding, you can create a test user by registering through the UI or using:

```bash
php artisan tinker
```

Then:

```php
$user = Modules\User\app\Models\User::factory()->create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
]);

$user->assignRole('admin');
```

Login credentials:
- Email: admin@example.com
- Password: password

## Frontend Features

### Available Pages

1. **Home** (`/`) - Landing page
2. **Login** (`/login`) - User authentication
3. **Register** (`/register`) - User registration
4. **Forgot Password** (`/forgot-password`) - Password reset request
5. **Reset Password** (`/reset-password`) - Password reset with token
6. **Dashboard** (`/dashboard`) - Protected user dashboard
7. **Profile** (`/profile`) - Protected user profile

### Language Switching

The application supports three languages:
- English (en) - Default
- Spanish (es)
- French (fr)

To switch languages programmatically:

```javascript
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();
locale.value = 'es'; // Switch to Spanish
```

### API Integration

The frontend is pre-configured to communicate with the Laravel backend API at `/api/v1`. All authentication endpoints are ready to use.

## Development Workflow

### Hot Module Replacement (HMR)

During development, Vite provides instant hot module replacement:

```bash
npm run dev
```

Changes to Vue components will be reflected immediately without page refresh.

### Code Formatting

```bash
# Format PHP code
./vendor/bin/pint

# Format JavaScript/Vue (if ESLint/Prettier configured)
npm run lint
```

### Building for Production

```bash
# Build optimized assets
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Troubleshooting

### Issue: "Vite manifest not found"

**Solution**: Run `npm run build` or `npm run dev`

### Issue: "CSRF token mismatch"

**Solution**: Clear cache and restart server:
```bash
php artisan config:clear
php artisan cache:clear
php artisan serve
```

### Issue: "401 Unauthorized" errors

**Solution**: Check that:
1. You're logged in
2. Token is stored in localStorage
3. API routes are correct
4. Sanctum is properly configured

### Issue: Module build errors

**Solution**: Clear node_modules and reinstall:
```bash
rm -rf node_modules package-lock.json
npm install
```

## Next Steps

1. **Customize** - Modify components in `resources/js/components`
2. **Add Pages** - Create new pages in `resources/js/pages`
3. **Add Routes** - Update `resources/js/router/index.js`
4. **Add Translations** - Update files in `resources/js/i18n/locales`
5. **Styling** - Customize Tailwind in `tailwind.config.js`

## Learn More

- [Frontend Documentation](FRONTEND_DOCUMENTATION.md) - Comprehensive frontend guide
- [Architecture Documentation](ARCHITECTURE.md) - System architecture
- [Security Guide](SECURITY.md) - Security best practices
- [API Documentation](Modules/Auth/README.md) - Authentication API

## Support

For issues or questions:
1. Check the [Frontend Documentation](FRONTEND_DOCUMENTATION.md)
2. Review existing [GitHub Issues](https://github.com/kasunvimarshana/ModularSaaS-LaravelVue/issues)
3. Create a new issue if needed

---

Happy coding! 🚀
