# AutoERP Platform

An enterprise-grade, production-ready, modular ERP SaaS platform built with Laravel 11 and Vue.js 3, following Clean Architecture principles and SOLID design patterns.

## 🚀 Features

### Core Features
- ✅ **Multi-Tenancy** - Complete tenant isolation with subscription management
- ✅ **Multi-Organization** - Nested organization hierarchies
- ✅ **Multi-Branch** - Multiple branches per organization
- ✅ **Multi-Location** - Warehouse location management (aisle, shelf, bin)
- ✅ **Multi-Currency** - Support for multiple currencies
- ✅ **Multi-Language** - Internationalization (i18n) support
- ✅ **Multi-Timezone** - Timezone-aware operations
- ✅ **RBAC/ABAC** - Role and attribute-based access control

### Product Management
- ✅ **Flexible Product Types** - Inventory, service, combo, bundle, digital
- ✅ **Comprehensive Pricing** - Buying/selling prices with dynamic pricing rules
- ✅ **Discount Management** - Flat, percentage, and tiered discounts
- ✅ **Profit Margins** - Automatic calculation of profit margins
- ✅ **Multi-Unit Support** - Different units for buying, selling, and stock
- ✅ **Price Lists** - Customer-specific, seasonal, and promotional pricing
- ✅ **Tax Management** - VAT, GST, and custom tax rates

### Inventory Management
- ✅ **Append-Only Stock Ledger** - Immutable inventory tracking
- ✅ **Batch/Lot/Serial Tracking** - Complete traceability
- ✅ **Expiry Management** - Track expiry dates with alerts
- ✅ **FIFO/FEFO** - First-In-First-Out / First-Expiry-First-Out valuation
- ✅ **Multi-Location Stock** - Track stock across multiple locations
- ✅ **Reorder Management** - Min/max stock levels with reorder points

## 🏗️ Architecture

### Clean Architecture
The application follows Clean Architecture with clear separation:
- **Controllers** → Handle HTTP requests/responses
- **Services** → Business logic and transaction orchestration
- **Repositories** → Data access layer
- **Models** → Domain entities
- **DTOs** → Data transfer objects

### Technology Stack

**Backend:**
- Laravel 11 (PHP 8.3)
- MySQL/PostgreSQL
- Laravel Sanctum (API Authentication)
- Spatie Laravel Permission (RBAC)

**Frontend:**
- Vue.js 3
- Vite
- Vue Router (planned)
- Pinia (state management - planned)
- Vue I18n (internationalization - planned)

## 📋 Prerequisites

- PHP 8.3 or higher
- Composer
- Node.js 20 or higher
- NPM or Yarn
- MySQL 8.0+ or PostgreSQL 14+
- Git

## 🛠️ Installation

### 1. Clone the Repository

```bash
git clone https://github.com/kasunvimarshana/AutoERP.git
cd AutoERP
```

### 2. Backend Setup

```bash
cd backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env file
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=autoerp
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate

# (Optional) Seed the database
php artisan db:seed

# Create storage link
php artisan storage:link

# Start the development server
php artisan serve
```

The API will be available at `http://localhost:8000`

### 3. Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Start the development server
npm run dev
```

The frontend will be available at `http://localhost:5173`

## 📚 Database Schema

### Core Tables

#### Multi-Tenancy
- `tenants` - Tenant information with subscription tracking
- `subscription_plans` - Available subscription plans
- `organizations` - Nested organization structures
- `branches` - Physical locations
- `locations` - Warehouse locations (bins, shelves, aisles)
- `users` - User accounts with tenant scoping

#### Product Management
- `product_categories` - Product categorization
- `products` - Main product catalog
- `product_variants` - Product variations
- `price_lists` - Dynamic pricing rules
- `price_list_items` - Product-specific prices

#### Inventory
- `stock_ledgers` - Append-only inventory transactions
- `units_of_measure` - Units for measurements

#### Master Data
- `currencies` - Currency definitions
- `tax_rates` - Tax rate configurations
- `countries` - Country master data

## 🔐 Security

- ✅ Tenant isolation at database level
- ✅ Global scopes for automatic tenant filtering
- ✅ Audit trails (created_by, updated_by)
- ✅ Soft deletes for data recovery
- ✅ UUID for external identifiers
- ✅ API token authentication
- ✅ Role and permission-based access control
- ✅ Input validation
- ✅ CSRF protection

## 🧪 Testing

```bash
cd backend

# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## 📖 API Documentation

API documentation will be available via OpenAPI (Swagger) specifications.

To generate API docs:
```bash
php artisan l5-swagger:generate
```

Access documentation at: `http://localhost:8000/api/documentation`

## 🔄 Development Workflow

### Backend Development
1. Create migration for database schema
2. Create model with relationships and traits
3. Create repository interface and implementation
4. Create service with business logic
5. Create controller for API endpoints
6. Create request validation classes
7. Create resource transformers
8. Write tests

### Frontend Development
1. Create Vue components
2. Implement API service calls
3. Create views for pages
4. Setup routing
5. Implement state management
6. Add i18n translations
7. Write component tests

## 📦 Module Structure

```
app/Modules/
├── IAM/              # Identity & Access Management
├── Tenant/           # Multi-tenancy
├── MasterData/       # Master data management
├── Product/          # Product management
├── Inventory/        # Inventory management
├── CRM/              # Customer relationship
├── Procurement/      # Purchase management
├── Sales/            # Sales management
├── POS/              # Point of sale
├── Invoice/          # Invoicing
├── Payment/          # Payment processing
├── Taxation/         # Tax management
├── Manufacturing/    # Manufacturing (planned)
├── Warehouse/        # Warehouse operations (planned)
├── Reporting/        # Reporting (planned)
└── Analytics/        # Analytics (planned)
```

## 🗺️ Roadmap

- [ ] Complete IAM module with authentication endpoints
- [ ] Implement CRM module
- [ ] Build Procurement module
- [ ] Create Sales and POS modules
- [ ] Develop Invoicing system
- [ ] Implement Payment processing
- [ ] Build Reporting engine
- [ ] Create Analytics dashboards
- [ ] Add Manufacturing workflows
- [ ] Implement push notifications
- [ ] Create mobile applications
- [ ] Advanced security features (2FA)
- [ ] CI/CD pipeline

## 🤝 Contributing

This project follows enterprise-grade development standards:
- Clean Architecture principles
- SOLID design patterns
- Comprehensive test coverage
- Detailed documentation
- Code review process

## 📄 License

Proprietary - All Rights Reserved

## 👥 Authors

- Kasun Vimarshana

## 📞 Support

For support and questions, please contact the development team.

## 🙏 Acknowledgments

- Laravel Framework
- Vue.js Framework
- Spatie Permission Package
- All open-source contributors

---

Built with ❤️ for enterprise scalability and maintainability
