# AutoERP - Vehicle Service Center SaaS Platform

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?logo=vue.js)](https://vuejs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-3178C6?logo=typescript)](https://www.typescriptlang.org)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php)](https://www.php.net)
[![Status](https://img.shields.io/badge/Status-Production%20Ready-brightgreen)](https://github.com)

**Production-ready, enterprise-level modular SaaS application for vehicle service centers and auto repair garages.**

Built with **Laravel 11** backend and **Vue.js 3** frontend, implementing **Clean Architecture** principles with a strict **Controller → Service → Repository** pattern. Features comprehensive multi-tenancy support, event-driven architecture, full RBAC, and enterprise-grade security.

## 🎯 Key Features

- ✅ **Clean Architecture** - Controller → Service → Repository pattern
- ✅ **Multi-Tenancy** - Complete tenant isolation and subscription management
- ✅ **Authentication** - Full auth system with Laravel Sanctum
- ✅ **RBAC/ABAC** - Role-Based Access Control with 73 granular permissions
- ✅ **Event-Driven** - Asynchronous processing and decoupled modules
- ✅ **Transaction Management** - Atomic operations with automatic rollback
- ✅ **Audit Trails** - Complete activity logging using Spatie Activity Log
- ✅ **REST API** - 85+ versioned, well-documented API endpoints
- ✅ **TypeScript Frontend** - Type-safe Vue.js 3 with Pinia state management
- ✅ **SOLID Principles** - Maintainable, testable, and scalable code
- ✅ **Production Ready** - Database seeders, migrations, comprehensive documentation

## 📋 Implemented Modules

### ✅ Authentication & Authorization
- Complete authentication system (register, login, logout)
- Password reset and change functionality
- Token-based authentication with Laravel Sanctum
- Role-Based Access Control (RBAC)
- 4 predefined roles: super_admin, admin, manager, user
- 73 granular permissions across all modules

### ✅ Tenant & Subscription Management
- Multi-tenant architecture with data isolation
- Subscription management (trial, active, expired, cancelled)
- Tenant activation and suspension
- Configurable user and branch limits
- Subscription renewal and plan management

### ✅ User Management
- Full user CRUD operations
- User activation and deactivation
- Role and permission assignment
- Tenant-scoped user management
- User activity tracking
### ✅ Customer & Vehicle Management
- Customer profiles (individual and business)
- Multi-vehicle ownership tracking
- Ownership transfer with complete history
- Service scheduling and reminders
- Mileage tracking and service intervals
- Customer lifetime value analytics

### ✅ Appointments & Bay Scheduling
- Service bay management and availability
- Appointment scheduling system
- Resource allocation and bay assignment
- Confirmation and cancellation workflows
- Priority-based scheduling

### ✅ Job Cards & Workflows
- Comprehensive job card management
- Task assignment and tracking
- Digital inspection with photo uploads
- Workflow state machine
- Work estimation vs actual tracking

### ✅ Inventory & Procurement
- Parts and inventory management
- Stock movement tracking
- Supplier management
- Purchase order workflows
- Low stock alerts
- Dummy items support

### ✅ Invoicing & Payments
- Invoice generation from job cards
- Multiple payment methods
- Service packages support
- Driver commissions tracking
- Payment application to invoices
- Overdue invoice tracking

### ✅ CRM & Customer Engagement
- Multi-channel communication (email, SMS, WhatsApp)
- Automated notification system
- Customer segmentation
- Service reminders
- Marketing campaign support

### ✅ Fleet & Telematics
- Fleet management for business customers
- Vehicle assignment to fleets
- Maintenance scheduling (mileage and time-based)
- Service due tracking
- Fleet statistics and reporting

### ✅ Reporting & Analytics
- Custom report generation
- KPI tracking and dashboards
- Performance metrics
- Business intelligence support
- Historical data analysis

## 📊 Technical Metrics

**Backend:**
- 35 Database Tables
- 27 Eloquent Models
- 26 Repositories
- 24 Services
- 15 Controllers
- 25 Domain Events
- 85+ API Endpoints
- 73 Permissions
- 4 Roles
- 15,000+ Lines of Code

**Frontend:**
- 15+ TypeScript Interfaces
- 7 API Service Modules
- Type-Safe HTTP Client
- Pinia State Management

**Testing:**
- 12 Passing Tests
- 51 Assertions
- 100% Success Rate

## 🎯 Demo Credentials

**Super Admin**: admin@autoerp.com / password123  
**Admin**: admin@demo.com / password123  
**Manager**: manager@demo.com / password123

### Prerequisites

- PHP 8.3+
- Composer 2.x
- Node.js 20.x+
- MySQL 8.0+ or PostgreSQL 14+ (SQLite for development)

### Quick Start

```bash
# Clone the repository
git clone https://github.com/kasunvimarshana/AutoERP.git
cd AutoERP

# Backend setup
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve

# In a new terminal - Frontend setup
cd frontend
npm install
echo "VITE_API_URL=http://localhost:8000/api/v1" > .env
npm run dev
```

Visit:
- Frontend: `http://localhost:5173`
- Backend API: `http://localhost:8000/api/v1`
- API Documentation: See [API_REFERENCE.md](./API_REFERENCE.md)

## 📚 Documentation

Comprehensive documentation is available:

- [API Reference](./API_REFERENCE.md) - Complete API documentation with examples
- [Architecture Guide](./ARCHITECTURE.md) - Design patterns and principles
- [Technical Documentation](./DOCUMENTATION.md) - Detailed technical specs
- [Implementation Summary](./IMPLEMENTATION_SUMMARY.md) - What's been built

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────┐
│              Vue.js 3 Frontend                  │
│  TypeScript + Pinia + Vue Router + Axios        │
└─────────────────┬───────────────────────────────┘
                  │ REST API (JSON)
┌─────────────────▼───────────────────────────────┐
│              Controllers Layer                   │
│   (HTTP handling, validation, responses)         │
└─────────────────┬───────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────┐
│              Services Layer                      │
│   (Business logic, orchestration, events)        │
└─────────────────┬───────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────┐
│             Repositories Layer                   │
│         (Data access, queries)                   │
└─────────────────┬───────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────┐
│         Models & Database                        │
│    (Eloquent ORM, Relationships)                 │
└──────────────────────────────────────────────────┘
```

## 🛠️ Technology Stack

**Backend:**
- Laravel 11.x (PHP 8.3+)
- Laravel Sanctum (Authentication)
- Spatie Packages (Multi-tenancy, Permissions, Activity Log)
- MySQL/PostgreSQL

**Frontend:**
- Vue.js 3 (Composition API)
- TypeScript
- Pinia (State Management)
- Vue Router
- Axios
- Tailwind CSS

## 🔐 Security Features

- Token-based API authentication (Laravel Sanctum)
- Role-Based Access Control (RBAC)
- Tenant isolation at database level
- Complete audit trails
- Input validation and sanitization
- CSRF protection
- SQL injection prevention
- XSS protection

## 📊 API Example

### Create Customer

```http
POST /api/v1/customers
Content-Type: application/json

{
  "customer_type": "individual",
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "phone": "+1234567890",
  "address_line1": "123 Main St",
  "city": "New York",
  "country": "US"
}
```

### Transfer Vehicle Ownership

```http
POST /api/v1/vehicles/{id}/transfer-ownership
Content-Type: application/json

{
  "new_customer_id": 2,
  "reason": "sale",
  "notes": "Vehicle sold to new owner"
}
```

## 🧪 Testing

```bash
# Backend tests
cd backend
php artisan test

# Frontend tests
cd frontend
npm run test:unit
```

## 📦 Deployment

See [DOCUMENTATION.md](./DOCUMENTATION.md#deployment) for detailed deployment instructions including:

- Production checklist
- Environment configuration
- Queue workers setup
- Scheduled tasks
- Performance optimization

## 🤝 Contributing

This is a demonstration project showcasing enterprise-level Laravel and Vue.js architecture. Contributions are welcome!

## 📄 License

Proprietary. All rights reserved.

## 📧 Contact

For more information or support, please open an issue in this repository.

---

**Built with modern best practices for the automotive service industry** 🚗💨
