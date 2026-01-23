# Authentication Module Setup Guide

## Overview

This guide provides step-by-step instructions for setting up and configuring the Authentication module in the ModularSaaS Laravel application.

## Prerequisites

- PHP 8.2+ (8.3+ recommended)
- Composer 2.x
- MySQL 5.7+ or PostgreSQL 10+
- Redis (optional, for caching and queues)
- Laravel 11.x

## Installation Steps

### 1. Install Dependencies

```bash
composer install
```

### 2. Environment Configuration

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

#### Required Environment Variables

```env
# Application
APP_NAME="ModularSaaS"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=modular_saas
DB_USERNAME=root
DB_PASSWORD=

# Mail (for email verification and password reset)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@modularsaas.com"
MAIL_FROM_NAME="${APP_NAME}"

# Queue (optional, recommended for production)
QUEUE_CONNECTION=sync  # Use 'redis' or 'database' in production

# Session
SESSION_DRIVER=file  # Use 'redis' or 'database' in production
SESSION_LIFETIME=120

# Cache
CACHE_DRIVER=file  # Use 'redis' in production

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

#### Optional Authentication Configuration

```env
# Authentication Rate Limiting
AUTH_LOGIN_MAX_ATTEMPTS=5
AUTH_LOGIN_DECAY_MINUTES=1
AUTH_REGISTER_MAX_ATTEMPTS=5
AUTH_REGISTER_DECAY_MINUTES=1
AUTH_PASSWORD_RESET_MAX_ATTEMPTS=5
AUTH_PASSWORD_RESET_DECAY_MINUTES=1

# Email Verification
AUTH_EMAIL_VERIFICATION_ENABLED=true
AUTH_EMAIL_VERIFICATION_EXPIRES_IN=60

# Password Reset
AUTH_PASSWORD_RESET_EXPIRES_IN=60

# Default Role
AUTH_DEFAULT_ROLE=user

# Token Configuration
AUTH_TOKEN_NAME=auth-token
AUTH_TOKEN_EXPIRES_IN=null  # null = never expires
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Run Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `users` - User accounts
- `roles` - User roles
- `permissions` - System permissions
- `role_has_permissions` - Role-permission relationships
- `model_has_roles` - User-role relationships
- `model_has_permissions` - Direct user permissions
- `personal_access_tokens` - API tokens
- `password_reset_tokens` - Password reset tokens
- Additional tables for multi-tenancy (if enabled)

### 5. Seed Roles and Permissions

```bash
php artisan auth:seed-roles
```

This command creates the following default roles:

| Role | Permissions | Description |
|------|-------------|-------------|
| **super-admin** | All permissions | Complete system access |
| **admin** | User CRUD, Role read/assign, Audit read | Administrative access |
| **manager** | User create/read/update, Role read | Management access |
| **user** | Read own profile, Read tenant | Standard user access |
| **guest** | Read tenant only | Minimal access |

To reset and reseed (removes existing roles and permissions):

```bash
php artisan auth:seed-roles --fresh
```

### 6. Configure Mail Service (Optional but Recommended)

For email verification and password reset to work, configure a mail service:

#### Using Mailtrap (Development)

1. Sign up at [mailtrap.io](https://mailtrap.io)
2. Get your SMTP credentials
3. Update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
```

#### Using Gmail (Development/Testing)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
```

### 7. Set Up Queue Workers (Optional but Recommended)

For better performance, use queue workers for sending emails:

```bash
# Update .env
QUEUE_CONNECTION=database

# Run migration for jobs table (if using database queue)
php artisan queue:table
php artisan migrate

# Start queue worker
php artisan queue:work
```

## Testing the Installation

### 1. Check if the module is enabled

```bash
php artisan module:list
```

You should see:
```
+------+---------+---------+------+
| Name | Status  | Order   | Path |
+------+---------+---------+------+
| Auth | Enabled | 0       | ...  |
| User | Enabled | 0       | ...  |
+------+---------+---------+------+
```

### 2. Test the API endpoints

Start the development server:

```bash
php artisan serve
```

#### Register a new user

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'
```

Expected response:
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "Test User",
      "email": "test@example.com",
      "roles": ["user"],
      "permissions": []
    },
    "token": "1|abc123..."
  }
}
```

#### Login

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Password123!"
  }'
```

#### Get authenticated user profile

```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 3. Run tests

```bash
php artisan test --filter=Auth
```

## Common Issues and Solutions

### Issue: "Too many attempts"

**Problem**: Rate limiting is triggered.

**Solution**: Wait for the decay period (default 1 minute) or increase limits in config:

```env
AUTH_LOGIN_MAX_ATTEMPTS=10
AUTH_LOGIN_DECAY_MINUTES=5
```

### Issue: Email verification not working

**Problem**: Mail service not configured.

**Solution**:
1. Check mail configuration in `.env`
2. Test mail connection: `php artisan tinker` then `Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); });`
3. Check logs: `storage/logs/laravel.log`

### Issue: Roles and permissions not working

**Problem**: Roles not seeded or cache issue.

**Solution**:
```bash
# Reseed roles
php artisan auth:seed-roles --fresh

# Clear cache
php artisan cache:clear
php artisan config:clear
```

### Issue: Token authentication not working

**Problem**: Sanctum guard not configured.

**Solution**:
1. Ensure `config/auth.php` has sanctum guard
2. Clear config cache: `php artisan config:clear`
3. Check middleware in routes

## Production Deployment

### 1. Environment Configuration

```env
APP_ENV=production
APP_DEBUG=false
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

### 2. Optimize Application

```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Set Up Queue Workers

Use Supervisor to keep queue workers running:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/worker.log
```

### 4. Configure HTTPS

Ensure your application uses HTTPS in production:

```env
APP_URL=https://yourdomain.com
SESSION_SECURE_COOKIE=true
```

### 5. Monitor Logs

Set up log monitoring for the auth channel:

```bash
tail -f storage/logs/auth.log
```

## Security Checklist

- [ ] HTTPS enabled in production
- [ ] Strong password policy enforced
- [ ] Rate limiting configured appropriately
- [ ] Email verification enabled
- [ ] Audit logging configured
- [ ] Queue workers running
- [ ] Regular backups scheduled
- [ ] Firewall configured
- [ ] Database credentials secured
- [ ] `.env` file not in version control

## Support

For issues or questions:

1. Check the [SECURITY.md](../SECURITY.md) documentation
2. Review [ARCHITECTURE.md](../ARCHITECTURE.md) for system design
3. Check module README: `Modules/Auth/README.md`
4. Review existing tests for usage examples

## License

This module is part of the ModularSaaS application and follows the same license.
