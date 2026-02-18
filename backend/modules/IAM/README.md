# IAM (Identity and Access Management) Module

Comprehensive authentication and authorization system for AutoERP.

## Features

- User authentication (login, register, logout)
- Password management (change, reset)
- Role-Based Access Control (RBAC)
- Permission management
- Multi-Factor Authentication (MFA)
- Multi-tenant support
- Rate limiting
- Audit logging

## Quick Start

1. Run migrations:
```bash
php artisan migrate
```

2. Seed initial data:
```bash
php artisan db:seed --class=Modules\\IAM\\Database\\Seeders\\IAMSeeder
```

3. Use API endpoints (see routes/api.php)

## System Roles

- **super-admin**: Full access
- **admin**: Management access
- **manager**: Limited management
- **user**: Basic access

## Testing

```bash
php artisan test modules/IAM/tests
```
