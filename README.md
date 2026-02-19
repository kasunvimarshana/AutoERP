# Multi-Tenant Enterprise ERP/CRM SaaS Platform

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-purple.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-passing-brightgreen.svg)](tests)
[![Code Style](https://img.shields.io/badge/code%20style-PSR--12-orange.svg)](https://www.php-fig.org/psr/psr-12/)

A comprehensive, modular, enterprise-grade ERP/CRM SaaS platform built with Laravel 12.x and Vue, featuring multi-tenancy, hierarchical organizations, and plugin-style architecture.

## ✨ Key Features

### 🏗️ Architecture
- **Clean Architecture** with strict layer separation
- **Domain-Driven Design (DDD)** with clear domain boundaries
- **Modular Plugin-Style** - 16 fully isolated, independently installable modules
- **Zero Circular Dependencies** - Strict module isolation
- **Event-Driven** workflows using native Laravel events and queues

### 🔐 Security & Multi-Tenancy
- **Stateless JWT Authentication** - No server sessions
- **Multi-Device Support** - Per user × device × organization tokens
- **Strict Tenant Isolation** - Complete data separation
- **Hierarchical Organizations** - Nested organizational structures
- **RBAC/ABAC** - Role and Attribute-Based Access Control

### 💰 Financial Management
- **Precision-Safe Calculations** - BCMath for all financial operations
- **Atomic Transactions** - Database transactions for data integrity
- **Optimistic & Pessimistic Locking** - Concurrency control
- **Comprehensive Audit Logging** - Full audit trails

### 🎯 Business Modules

#### Product & Pricing
- Flexible product types (goods, services, bundles, composites)
- Multi-unit support with conversions
- Location-based pricing
- 6 extensible pricing strategies (flat, percentage, tiered, volume, time-based, rule-based)

#### CRM
- Customer relationship management
- Lead tracking and conversion
- Sales opportunity pipeline
- Contact management

#### Sales
- Quotation management
- Order processing
- Invoice generation and payments
- Multi-currency support

#### Purchase
- Vendor management
- Purchase order processing
- Goods receipt tracking
- Bill management and payments

#### Inventory
- Multi-warehouse management
- Stock movements and transfers
- Inventory valuation (FIFO, LIFO, Average)
- Stock counts and adjustments
- Serial number tracking
- Reorder point management

#### Accounting
- Chart of accounts management
- Journal entries
- General ledger
- Financial statements (Balance Sheet, Income Statement, Cash Flow)
- Trial balance
- Fiscal period management

## 📋 Requirements

- **PHP**: 8.2 or higher
- **Extensions**: BCMath, PDO, Mbstring, OpenSSL, JSON
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Node.js**: 18+ (for frontend build)
- **Composer**: 2.x

## 🚀 Quick Start

### Installation

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
php artisan migrate
php artisan db:seed

# Build frontend assets
npm run build

# Start development server
composer run dev
```

### Running Tests

```bash
composer test
```

### Code Style

```bash
# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

## 📚 Documentation

**[📖 Complete Documentation Index](docs/README.md)** - Full documentation catalog

### Quick Links

- **[Architecture Guide](docs/architecture/ARCHITECTURE.md)** - Comprehensive architecture documentation
- **[Deployment Guide](docs/guides/DEPLOYMENT_GUIDE.md)** - Production deployment instructions
- **[API Quick Start](docs/api/API_QUICK_START.md)** - API documentation and examples
- **[API Documentation](docs/api/API_DOCUMENTATION.md)** - Complete API reference
- **[Compliance Report](docs/reports/COMPLIANCE_REPORT_2026.md)** - Latest audit findings
- **[System Overview](docs/SYSTEM_OVERVIEW.md)** - High-level system overview

## 🏛️ Module Structure

```
modules/
├── Core/          # Foundation (transactions, math helpers, base repositories)
├── Tenant/        # Multi-tenancy and organizations
├── Auth/          # JWT authentication and RBAC
├── Audit/         # Audit logging system
├── Product/       # Product management
├── Pricing/       # Pricing strategies
├── CRM/           # Customer relationship management
├── Sales/         # Quotations, orders, invoices
├── Purchase/      # Vendors, POs, bills
├── Inventory/     # Warehouse and stock management
├── Accounting/    # Financial accounting
├── Notification/  # Multi-channel notifications
├── Billing/       # Subscription billing
├── Reporting/     # Report builder and analytics
├── Document/      # Document management
└── Workflow/      # Workflow automation
```

## 🔑 API Authentication

```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password",
    "device_name": "Web Browser",
    "organization_id": 1
  }'

# Use token in requests
curl -X GET http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer {your-token}" \
  -H "X-Tenant-ID: 1" \
  -H "X-Organization-ID: 1"
```

## 🧪 Testing

The project includes comprehensive test coverage:

```
✓ 42 tests passing
✓ 88 assertions
✓ Core services fully tested
✓ Authentication workflows verified
✓ Financial calculations validated
```

Run specific test suites:

```bash
# Unit tests
php artisan test --testsuite=Unit

# Feature tests
php artisan test --testsuite=Feature

# Specific test file
php artisan test tests/Unit/Core/CodeGeneratorServiceTest.php
```

## 📊 Code Quality Metrics

- **Architecture Compliance**: 95%+
- **Code Style**: PSR-12 (100% via Laravel Pint)
- **BCMath Usage**: 100% (all financial calculations)
- **Transaction Safety**: 100% (all multi-step operations)
- **Circular Dependencies**: 0 (zero detected)
- **Test Coverage**: Core services 100%

## 🛠️ Development

### Project Scripts

```bash
# Development server (all services)
composer run dev

# Setup project from scratch
composer run setup

# Run tests
composer run test
```

### Code Standards

- **PSR-12** code style
- **Strict types** enabled (`declare(strict_types=1)`)
- **Type hints** for all parameters and return types
- **DocBlocks** for all public methods
- **Native Laravel features only** - no unnecessary dependencies

## 🚢 Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for comprehensive production deployment instructions including:
- Server requirements
- Security checklist
- Performance optimization
- Monitoring setup
- Backup strategies
- Scaling guidelines

## 🏗️ Architecture Highlights

### Clean Architecture Layers

```
Controllers → Services → Repositories → Models
     ↓           ↓            ↓
   HTTP      Business     Data Access
```

### Module Dependencies

```
Core → Tenant → Auth → Audit
            ↓      ↓
        Product → Pricing
            ↓      ↓
          CRM ← Sales → Purchase
            ↓      ↓
        Inventory → Accounting
```

### Design Patterns

- **Repository Pattern** - Data access abstraction
- **Service Layer** - Business logic encapsulation
- **Factory Pattern** - Object creation
- **Observer Pattern** - Event-driven workflows
- **Strategy Pattern** - Pluggable pricing engines
- **Pipeline Pattern** - Workflow processing

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Guidelines

- Follow PSR-12 code style
- Write tests for new features
- Update documentation
- Use semantic commit messages
- Ensure all tests pass before submitting PR

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

Built with:
- [Laravel](https://laravel.com) - The PHP Framework
- [Vue.js](https://vuejs.org) - Progressive JavaScript Framework
- [Tailwind CSS](https://tailwindcss.com) - Utility-first CSS framework

Inspired by:
- Clean Architecture principles
- Domain-Driven Design

## 📞 Support

- **Documentation**: [Full Documentation](docs/)
- **Issues**: [GitHub Issues](https://github.com/kasunvimarshana/AutoERP/issues)
- **Email**: kasunvmail@gmail.com

## 🗺️ Roadmap

### Version 1.1 (Planned)
- [ ] Multi-language support
- [ ] Advanced reporting dashboard
- [ ] Mobile app API enhancements
- [ ] Real-time notifications via WebSockets

### Version 2.0 (Future)
- [ ] AI-powered insights
- [ ] Blockchain integration for immutable audit trails
- [ ] GraphQL API
- [ ] Microservices architecture option

---

**Built with ❤️ using Laravel 12 & Vue**
