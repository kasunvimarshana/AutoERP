# Modular SaaS Vehicle Service Application

A production-ready, enterprise-grade modular SaaS application for vehicle service centers and auto repair garages built with Laravel and Vue.js.

## 🏗️ Architecture

This application implements **Clean Architecture** with strict adherence to:
- **Controller → Service → Repository** pattern
- **SOLID** principles
- **DRY** (Don't Repeat Yourself)
- **KISS** (Keep It Simple, Stupid)

## ✨ Key Features

### Core Modules

1. **Customer & Vehicle Management**
   - Customer profiles and relationships
   - Vehicle registration and ownership tracking
   - Service history across all branches
   - Meter readings and maintenance tracking

2. **Appointments & Bay Scheduling**
3. **Job Cards & Workflows**
4. **Inventory & Procurement**
5. **Invoicing & Payments**
6. **CRM & Customer Engagement**
7. **Fleet & Telematics**
8. **Reporting & Analytics**

### Technical Features

- **Multi-Tenancy**: Complete tenant isolation
- **Multi-Branch**: Operations across multiple service centers
- **RBAC/ABAC**: Role and attribute-based access control
- **Transaction Management**: Explicit boundaries, rollback mechanisms
- **Event-Driven**: Asynchronous workflows via events
- **REST API**: Clean, versioned API endpoints
- **i18n**: Full internationalization support

## 📁 Project Structure

```
ModularSaaS-LaravelVue/
├── app/
│   ├── Contracts/          # Interfaces for patterns
│   ├── Repositories/       # Base repository implementation
│   └── Services/           # Base service implementation
├── modules/                # Modular architecture
│   ├── Customer/           # Complete example module
│   ├── Vehicle/            # Cross-module interaction example
│   └── [Other Modules]/
├── docs/                   # Comprehensive documentation
└── tests/                  # Unit and integration tests
```

## 🚀 Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL/PostgreSQL or SQLite

### Installation

```bash
# Clone repository
git clone https://github.com/kasunvimarshana/ModularSaaS-LaravelVue.git
cd ModularSaaS-LaravelVue

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run build
```

## 📚 Documentation

- [Architecture Overview](ARCHITECTURE.md)
- [Module Development Guide](docs/MODULE_DEVELOPMENT.md)
- [Database Schema](docs/DATABASE.md)
- [API Documentation](docs/API.md)

## 🧪 Testing

```bash
php artisan test
```

## 🔒 Security

- Tenant isolation at query level
- RBAC/ABAC for access control
- Encryption at rest and in transit
- Immutable audit trails

## 🛠️ Technology Stack

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Vue.js 3
- **UI**: Tailwind CSS, AdminLTE
- **Database**: MySQL/PostgreSQL/SQLite
- **Authentication**: Laravel Sanctum

## 📝 Example Modules

- **Customer Module**: Complete CRUD with search and statistics
- **Vehicle Module**: Cross-module interactions, ownership transfer

## 📄 License

MIT License
