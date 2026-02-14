# AutoERP

> Production-ready, modular, ERP-grade SaaS platform built with Laravel and Vue.js

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-green.svg)](https://vuejs.org)
[![License](https://img.shields.io/badge/license-Proprietary-blue.svg)](LICENSE)

## Overview

AutoERP is an enterprise-grade, modular SaaS platform designed for complex business operations. Built with strict adherence to Clean Architecture, SOLID principles, and modern best practices, it provides a scalable foundation for ERP systems supporting multiple tenants, vendors, branches, languages, and currencies.

## Key Features

### 🏢 Multi-Tenancy Architecture
- **Strict Tenant Isolation**: Automatic data scoping per tenant
- **Single Database**: Efficient tenant-ID based isolation
- **Tenant Context**: Automatic detection from authenticated user

### 🌐 Multi-Everything Support
- **Multi-Vendor**: Support for multiple vendors per tenant
- **Multi-Branch**: Hierarchical branch management
- **Multi-Language**: Backend and frontend i18n
- **Multi-Currency**: Currency-aware transactions

### 🔐 Enterprise Security
- **Authentication**: Laravel Sanctum token-based auth
- **Authorization**: RBAC and ABAC with Spatie Permission
- **Data Encryption**: At-rest encryption
- **Audit Trails**: Immutable activity logging
- **Rate Limiting**: API throttling
- **CSRF Protection**: Cross-site request forgery prevention

### 🎯 ERP Modules

#### Core Modules
- **Tenant Management**: Subscription and tenant administration
- **User Management**: Users, roles, and permissions
- **Branch & Vendor Management**: Organizational structure

#### Business Modules
- **CRM**: Customer relationship management, leads, campaigns
- **Inventory**: Stock management with ledger-based tracking
- **POS**: Point of sale system
- **Billing**: Invoicing, payments, and taxation
- **Fleet**: Vehicle and fleet management
- **Analytics**: Reports, dashboards, and KPIs

### 🏗️ Architecture

#### Clean Architecture
- **Separation of Concerns**: Distinct layer responsibilities
- **Dependency Inversion**: Framework-agnostic core
- **Testability**: Easy unit and integration testing

#### Design Patterns
- **Controller → Service → Repository**: Strict layered architecture
- **Service Orchestration**: Transactional business logic
- **Event-Driven**: Asynchronous workflow handling
- **Repository Pattern**: Data access abstraction

#### Code Quality
- **SOLID Principles**: Maintainable and extensible code
- **DRY**: Code reuse through inheritance and composition
- **KISS**: Simple, understandable solutions
- **PSR-12**: PHP coding standards

### 🔄 Service Orchestration
- **Transactional Boundaries**: Atomic operations with rollback
- **Exception Handling**: Consistent error propagation
- **Event Emission**: Domain events for async workflows
- **Idempotency**: Safe retry mechanisms

### 📡 API Design
- **RESTful**: Standard HTTP methods and status codes
- **Versioned**: `/api/v1/` prefix for version management
- **Swagger/OpenAPI**: Auto-generated documentation
- **Pagination**: Built-in pagination support
- **Rate Limited**: Throttling for abuse prevention

### 💻 Frontend

#### Vue.js 3 with Vite
- **Component-Based**: Reusable Vue components
- **State Management**: Pinia for predictable state
- **Routing**: Vue Router with auth guards
- **Internationalization**: Vue i18n for translations
- **Styling**: Tailwind CSS utility-first framework

#### Features
- **Modular Structure**: Feature-based organization
- **Responsive Design**: Mobile-first approach
- **Accessibility**: WCAG compliance
- **Performance**: Lazy loading and code splitting

## Tech Stack

### Backend
- **Framework**: Laravel 12.x
- **PHP**: 8.1+
- **Database**: MySQL 8.0+ / PostgreSQL 13+
- **Cache**: Redis 6.x
- **Queue**: Redis / Database
- **API Auth**: Laravel Sanctum
- **Permissions**: Spatie Laravel Permission
- **API Docs**: L5-Swagger (OpenAPI)

### Frontend
- **Framework**: Vue.js 3.x
- **Build Tool**: Vite
- **State Management**: Pinia
- **Routing**: Vue Router 4.x
- **HTTP Client**: Axios
- **Styling**: Tailwind CSS 3.x
- **i18n**: Vue I18n 10.x

### Development Tools
- **Code Style**: Laravel Pint (PSR-12)
- **Testing**: PHPUnit, Pest
- **Package Manager**: Composer, NPM

## Quick Start

```bash
# Clone repository
git clone https://github.com/kasunvimarshana/AutoERP.git
cd AutoERP

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate --seed

# Build assets and start
npm run dev
php artisan serve
```

Visit `http://localhost:8000` to see the application.

For detailed setup instructions, see [SETUP.md](SETUP.md).

## Documentation

- **[Setup Guide](SETUP.md)**: Detailed installation and configuration
- **[Architecture](ARCHITECTURE.md)**: System design and patterns
- **API Documentation**: Available at `/api/documentation` after setup

## Project Structure

```
AutoERP/
├── app/
│   ├── Core/                # Core architecture (Repository, Service, Traits)
│   ├── Modules/             # Business domain modules
│   └── Support/             # Helper services
├── database/
│   ├── migrations/          # Database migrations
│   └── seeders/             # Database seeders
├── resources/
│   ├── js/                  # Vue.js application
│   │   ├── modules/         # Feature modules
│   │   ├── components/      # Shared components
│   │   ├── layouts/         # Layout components
│   │   ├── router/          # Vue Router
│   │   └── stores/          # Pinia stores
│   ├── css/                 # Stylesheets
│   └── views/               # Blade templates
├── routes/
│   ├── api.php              # API routes
│   └── web.php              # Web routes
├── tests/                   # Test suites
├── ARCHITECTURE.md          # Architecture documentation
├── SETUP.md                 # Setup guide
└── README.md                # This file
```

## Module Development

### Backend Module

```php
// 1. Create Repository
class UserRepository extends BaseRepository {
    protected function model(): string {
        return User::class;
    }
}

// 2. Create Service
class UserService extends BaseService {
    public function __construct(UserRepository $repository) {
        $this->repository = $repository;
    }
}

// 3. Create Controller
class UserController extends Controller {
    public function __construct(protected UserService $service) {}
    
    public function index() {
        return response()->json($this->service->getPaginated());
    }
}
```

### Frontend Module

```vue
<!-- Component -->
<template>
  <div>{{ user.name }}</div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useUserStore } from '@/stores/user';

const userStore = useUserStore();
const user = ref(null);

onMounted(async () => {
  user.value = await userStore.fetchUser();
});
</script>
```

## Testing

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test
php artisan test --filter=UserTest
```

## Deployment

### Production Checklist
- ✅ Environment variables configured
- ✅ Database migrations run
- ✅ Assets compiled (`npm run build`)
- ✅ Caches optimized (`php artisan optimize`)
- ✅ Queue workers running
- ✅ SSL certificates installed
- ✅ Backups configured
- ✅ Monitoring setup

### Docker

```bash
docker-compose up -d
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Code Standards

- Follow PSR-12 coding standards
- Write meaningful tests
- Document public APIs
- Use type hints
- Write clear commit messages

## License

Proprietary - All rights reserved

## Support

- **Issues**: [GitHub Issues](https://github.com/kasunvimarshana/AutoERP/issues)
- **Email**: support@autoerp.com
- **Documentation**: See [ARCHITECTURE.md](ARCHITECTURE.md) and [SETUP.md](SETUP.md)

## Roadmap

- [x] Core architecture scaffolding
- [x] Multi-tenancy implementation
- [x] Authentication & authorization
- [x] Base CRUD modules
- [x] Vue.js frontend foundation
- [ ] Complete all ERP modules
- [ ] Comprehensive test coverage
- [ ] Advanced analytics
- [ ] Mobile app support
- [ ] API v2 with GraphQL

---

Built with ❤️ for enterprise-grade ERP systems
