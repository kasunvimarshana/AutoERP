# AutoERP - Consolidated Requirements Document

## Document Purpose

This document consolidates and organizes all requirements, specifications, and architectural guidelines extracted from multiple iterations of project requirements. It serves as the single source of truth for understanding the complete scope and expectations of the AutoERP platform.

---

## Executive Summary

AutoERP is a production-ready, modular, ERP-grade SaaS platform built with Laravel (backend) and Vue.js with Vite (frontend). The platform implements Clean Architecture, Modular Architecture, and the Controller → Service → Repository pattern while strictly adhering to SOLID, DRY, and KISS principles.

### Core Principles

1. **Architectural Excellence**: Clean Architecture with clear separation of concerns
2. **Multi-Dimensional Support**: Multi-tenancy, multi-vendor, multi-branch, multi-language, multi-currency, multi-unit
3. **Security First**: Enterprise-grade security with RBAC/ABAC, encryption, audit trails
4. **Transaction Safety**: Service-layer orchestration with explicit transactional boundaries
5. **Event-Driven**: Asynchronous workflows via domain events without compromising consistency
6. **API-First**: Versioned REST APIs with comprehensive OpenAPI/Swagger documentation
7. **Production-Ready**: Fully scaffolded, LTS-ready, with complete DevOps support

---

## 1. Technology Stack Requirements

### Backend Technologies

**Core Framework**
- **Laravel**: 10+ or 11 (latest LTS version)
- **PHP**: 8.3+ (minimum 8.2)
- **Database**: PostgreSQL 15+ (preferred) or MySQL 8+
- **Cache & Queue**: Redis 7+
- **Search**: Elasticsearch/Meilisearch (optional)

**Authentication & Authorization**
- Laravel Sanctum (API authentication)
- Spatie Laravel Permission (RBAC implementation)
- Custom ABAC implementation

**Additional Backend Libraries**
- Spatie Laravel Activity Log (audit trails)
- L5-Swagger (OpenAPI/Swagger documentation)
- Native Laravel features or stable LTS libraries only

### Frontend Technologies

**Core Framework**
- **Vue.js**: 3.x with Composition API
- **TypeScript**: 5.x (strongly recommended)
- **Build Tool**: Vite (latest stable)

**UI & Styling**
- **CSS Framework**: Tailwind CSS 3.x
- **Admin Template**: AdminLTE (optional) or custom professional theme
- **Component Libraries**: Headless UI, Radix Vue (optional)

**State Management & Routing**
- **State**: Pinia (Vue 3 recommended state management)
- **Routing**: Vue Router 4
- **HTTP Client**: Axios
- **Internationalization**: Vue I18n 9

### Database Design

**Primary Database**
- PostgreSQL 15+ (preferred for JSONB support and advanced features)
- MySQL 8+ (alternative with JSON support)

**Design Principles**
- Tenant-aware schemas with tenant_id columns
- Append-only ledgers for critical data (inventory, transactions)
- Normalized tables with optional JSONB for flexible attributes
- Proper indexing for multi-tenant queries
- Foreign key constraints and cascading rules

### DevOps & Infrastructure

**Containerization**
- Docker 24+ and Docker Compose 2+
- Multi-stage Dockerfiles for optimization
- Separate containers for app, database, Redis, queue workers

**Orchestration (Production)**
- Kubernetes with Helm charts
- Horizontal Pod Autoscaling
- Service mesh (Istio/Linkerd - optional)

**CI/CD**
- GitHub Actions (primary)
- Automated testing on PRs
- Automated deployment to staging/production
- Code quality checks (PHPStan, ESLint, Prettier)

**Monitoring & Logging**
- Prometheus + Grafana (metrics)
- ELK Stack (Elasticsearch, Logstash, Kibana) for logging
- Sentry or similar for error tracking
- Structured logging with correlation IDs

---

## 2. Architectural Patterns & Principles

### Clean Architecture

**Layered Structure**
```
Presentation Layer (Controllers, Views, API Resources)
    ↓
Application Layer (Services, Use Cases, DTOs)
    ↓
Domain Layer (Models, Business Logic, Domain Events)
    ↓
Infrastructure Layer (Repositories, External Services, Database)
```

**Controller → Service → Repository Pattern**
- **Controllers**: Thin, handle HTTP concerns, validation, response formatting
- **Services**: Business logic, transaction orchestration, cross-module communication
- **Repositories**: Data access abstraction, query optimization
- **DTOs**: Data transfer objects for service layer communication

### SOLID Principles

1. **Single Responsibility**: Each class has one reason to change
2. **Open/Closed**: Open for extension, closed for modification
3. **Liskov Substitution**: Subtypes must be substitutable for base types
4. **Interface Segregation**: Clients shouldn't depend on unused interfaces
5. **Dependency Inversion**: Depend on abstractions, not concretions

### DRY & KISS

- **DRY (Don't Repeat Yourself)**: Eliminate code duplication through abstraction
- **KISS (Keep It Simple, Stupid)**: Favor simplicity over complexity
- Reusable components, services, and utilities
- Clear, readable code over clever solutions

### Modular Architecture

**Module Structure**
```
Modules/
├── Tenancy/
├── IAM/
├── CRM/
├── Inventory/
├── Billing/
├── Fleet/
├── Analytics/
└── Settings/
```

**Each Module Contains**
- Models (Domain entities)
- Repositories (Data access interfaces and implementations)
- Services (Business logic)
- Controllers (API endpoints)
- Requests (Form validation)
- Resources (API responses)
- Events & Listeners
- Policies (Authorization)
- Migrations
- Seeders
- Tests

**Module Communication**
- Modules communicate ONLY via service layer
- No direct model access across modules
- Use domain events for loose coupling
- Contracts/Interfaces for dependencies

---

## 3. Multi-Dimensional Support Requirements

### Multi-Tenancy

**Tenant Isolation Strategies**
- **Primary**: Single database with tenant_id column (recommended for most cases)
- **Advanced**: Database-per-tenant (for large enterprise clients)
- **Hybrid**: Configurable per tenant based on subscription tier

**Implementation Requirements**
- Global scopes on all tenant-aware models
- Tenant-aware authentication and session management
- Subdomain routing (e.g., `client1.autoerp.com`)
- Custom domain support (e.g., `erp.clientdomain.com`)
- Tenant context middleware for all requests
- Tenant-aware cache keys
- Tenant-aware queue jobs
- Strict data isolation (no cross-tenant queries)

**Tenant Management Features**
- Tenant registration and onboarding
- Subscription plans and billing
- Tenant activation/suspension/deletion
- Tenant-specific configuration
- Usage tracking and limits
- Tenant analytics dashboard

### Multi-Vendor

**Vendor Support**
- Multiple vendors per tenant (marketplace model)
- Vendor-specific catalogs and inventory
- Vendor-level permissions and access control
- Vendor settlements and payouts
- Vendor performance analytics

### Multi-Branch/Location

**Branch Management**
- Multiple branches/locations per tenant
- Branch-specific inventory and stock levels
- Inter-branch transfers and requisitions
- Branch-level reporting
- Branch-aware appointments and scheduling
- Cross-branch consolidated reporting

### Multi-Language (i18n)

**Backend Internationalization**
- Laravel localization for all backend messages
- Database-driven translations for dynamic content
- Language fallback mechanisms
- API responses include locale information

**Frontend Internationalization**
- Vue I18n for all UI text
- Shared translation keys with backend
- RTL (Right-to-Left) language support
- Language switcher in UI
- Locale-aware date/time/number formatting

**Supported Languages (Minimum)**
- English (default)
- Spanish
- French
- German
- Arabic (RTL)
- Extensible to add more languages

### Multi-Currency

**Currency Support**
- Multiple currencies per tenant
- Base currency configuration per tenant
- Real-time exchange rate management
- Historical exchange rate tracking
- Multi-currency pricing and invoicing
- Currency conversion with audit trail
- Multi-currency financial reporting

**Features**
- Currency master data management
- Exchange rate APIs integration (e.g., Open Exchange Rates)
- Currency-aware calculations and rounding
- Multi-currency bank accounts
- Foreign exchange gain/loss tracking

### Multi-Unit

**Unit of Measure Support**
- Multiple units per product (UoM)
- Base unit and alternate units
- Unit conversions and equivalents
- Unit-aware pricing
- Purchase unit vs. sales unit vs. stock unit
- Fractional quantities support

---

## 4. Core Modules Detailed Requirements

### 4.1 Identity & Access Management (IAM)

**Authentication**
- User registration with email verification
- Login with email/username and password
- Session-based authentication for web
- Token-based authentication (Sanctum) for API
- Multi-Factor Authentication (MFA/2FA)
  - TOTP (Time-based One-Time Password)
  - SMS-based OTP (via Twilio/SNS)
  - Email-based OTP
- Password reset/recovery
- Remember me functionality
- Account lockout after failed attempts
- Single Sign-On (SSO) - OAuth2/SAML (optional)

**Authorization**
- **RBAC (Role-Based Access Control)**
  - Predefined system roles (Super Admin, Tenant Admin, Manager, User, etc.)
  - Custom tenant-specific roles
  - Role hierarchy and inheritance
  - Role assignment to users
  
- **ABAC (Attribute-Based Access Control)**
  - Context-aware permissions (tenant, vendor, branch)
  - Dynamic permission evaluation
  - Resource-level permissions
  
- **Permissions**
  - Granular permissions per module and action
  - Permission grouping by module
  - Direct permission assignment to users (overrides)
  - Tenant-aware permissions
  
- **Policies**
  - Laravel Policy classes for all models
  - Policy-based authorization in controllers
  - API authorization guards

**User Management**
- User profiles (name, email, phone, avatar)
- User status management (active, inactive, suspended)
- User preferences and settings
- User activity tracking
- User session management
- User impersonation (admin feature)
- Bulk user import/export

**API Token Management**
- Personal access tokens (PAT)
- API tokens with scopes
- Token expiration policies
- Token revocation
- Token usage tracking

### 4.2 Tenant & Subscription Management

**Tenant Management**
- Tenant registration and onboarding workflow
- Tenant profile (name, domain, logo, settings)
- Tenant status (trial, active, suspended, cancelled)
- Tenant billing information
- Tenant configuration and preferences
- Tenant data export/backup
- Tenant deletion with data retention policies

**Subscription Management**
- Multiple subscription plans (Basic, Professional, Enterprise, etc.)
- Plan features and limits (users, branches, storage, API calls)
- Subscription lifecycle management (trial, active, expired, cancelled)
- Prorated billing for plan upgrades/downgrades
- Usage tracking against subscription limits
- Automatic renewal and reminders
- Dunning management for failed payments

**Organization Hierarchy**
- Organizations within tenants
- Departments and teams
- Organizational chart
- Organization-level settings

### 4.3 CRM (Customer Relationship Management)

**Customer Master Data**
- Customer profiles (individual and business)
  - Individual: First name, last name, email, phone, address
  - Business: Company name, tax ID, contact persons
- Customer types/categories
- Customer status (prospect, active, inactive)
- Customer tags and segmentation
- Credit limit management
- Payment terms and conditions

**Contact Management**
- Multiple contacts per customer
- Contact roles (primary, billing, technical)
- Contact communication preferences
- Contact history

**Lead Management**
- Lead capture and qualification
- Lead assignment and routing
- Lead scoring
- Lead conversion to customer
- Lead source tracking

**Customer Engagement**
- Interaction history (calls, emails, meetings, notes)
- Customer communication via multiple channels
- Email campaigns and templates
- Customer feedback and surveys
- Customer service tickets

**Customer Segmentation**
- Dynamic segmentation based on attributes
- Manual customer groups
- Segment-based pricing and promotions
- Segment analytics

### 4.4 Inventory & Procurement Management

**Product Master Data**
- **Product vs SKU (Stock Keeping Unit) Model**
  - Product: Abstract entity (e.g., "T-Shirt")
  - SKU: Sellable/stockable unit with specific attributes (e.g., "T-Shirt - Red - Large")
  - Only SKUs can be bought, sold, or stocked
  
- **Product Attributes**
  - Unlimited product variants (size, color, material, etc.)
  - Dynamic attributes using normalized tables + PostgreSQL JSONB
  - Attribute sets and templates
  - No frequent schema changes required

**SKU/Variant Management**
- SKU generation and barcode assignment
- Multiple SKUs per product
- SKU-specific pricing
- SKU images and descriptions
- SKU status (active, discontinued)

**Inventory Tracking**
- **Append-Only Stock Ledger**
  - All stock movements recorded as immutable entries
  - No mutable quantity fields on SKU records
  - Derived real-time stock balances via ledger aggregation
  
- **Stock Movement Types**
  - Purchase Receipt
  - Sales Order
  - Stock Transfer (between branches/warehouses)
  - Stock Adjustment (count corrections)
  - Return (customer return, supplier return)
  - Damage/Wastage
  - Reservation (for orders)
  
- **Multi-Location Inventory**
  - Real-time stock levels per SKU per location (warehouse/branch)
  - Inter-location transfers and requisitions
  - Location-specific min/max stock levels
  - Automated reordering per location

**Batch/Lot & Serial Tracking**
- Batch/Lot number tracking for inventory batches
- Serial number tracking for individual units
- FIFO (First In, First Out) fulfillment
- FEFO (First Expired, First Out) for expiry-sensitive items
- Expiry date tracking and alerts
- Batch recall capabilities

**Procurement**
- Supplier master data
- Purchase requisitions
- Request for Quotation (RFQ)
- Purchase orders (PO)
- Goods Receipt Note (GRN)
- Purchase returns
- Supplier invoices and payments
- Supplier performance tracking

**Barcode & QR Code**
- Barcode/QR code generation for SKUs
- Barcode scanning for stock operations
- Mobile scanning support

**Inventory Valuation**
- Multiple valuation methods (FIFO, Weighted Average)
- Batch-wise costing
- Multi-currency costing
- Inventory valuation reports

**Kitting & Bundling**
- Product bundles/kits
- Bill of Materials (BOM)
- Kit assembly and disassembly

### 4.5 Pricing Engine

**Multiple Price Lists**
- Multiple price lists per tenant
- Price list assignment rules (customer group, region, channel)
- Price list priority and fallback
- Price list activation dates (time-based validity)

**Dynamic Pricing Rules**
- Context-aware pricing (customer, quantity, currency, region, channel)
- Quantity tiers (volume-based discounts)
- Customer-specific pricing
- Promotional pricing with validity periods
- Rule-based price resolution engine

**Price History**
- Historical price tracking
- Price change audit trail
- Price comparison and analysis

### 4.6 Billing, Invoicing & Payments

**Invoicing**
- Quote/Proforma invoice generation
- Tax invoice generation
- Invoice templates (customizable per tenant)
- Multiple invoice series/numbering
- Invoice status tracking (draft, sent, paid, overdue, cancelled)
- Recurring invoices
- Partial invoicing
- Invoice amendments and credit notes

**Taxation**
- Multi-jurisdiction tax support (GST, VAT, Sales Tax)
- Tax rules and rates management
- Tax calculation based on customer/product/location
- Tax inclusive vs. exclusive pricing
- Tax reporting and compliance

**Payments**
- Multiple payment methods (cash, card, bank transfer, e-wallet)
- Payment gateway integration (Stripe, PayPal, Razorpay, etc.)
- Payment recording and reconciliation
- Partial payments
- Payment refunds
- Payment reminders for overdue invoices
- Payment terms (net 30, net 60, etc.)

**Credit Management**
- Credit limit per customer
- Credit hold and release
- Credit utilization tracking
- Aging analysis (30/60/90 days)

### 4.7 Point of Sale (POS)

**POS Core Features**
- Fast, responsive checkout interface
- Barcode scanning
- Product search and selection
- Cart management (add, remove, update quantity)
- Apply discounts (item-level, cart-level)
- Multiple payment methods in single transaction
- Split payments
- Receipt printing (thermal printers)
- Email receipts

**POS Transactions**
- Cash sales
- Credit sales
- Sales returns
- Exchanges
- Layaway/on-hold orders
- Customer loyalty points (optional)

**POS Inventory**
- Real-time stock visibility at POS
- Stock reservation during checkout
- Low stock alerts
- Stock transfer requests from POS

**POS Reporting**
- Daily sales summary
- Cashier performance reports
- Sales by product/category
- Payment method breakdown
- Cash drawer reconciliation (opening/closing balance)

**Offline Mode (Optional)**
- POS works offline with local storage
- Sync when connection restored
- Conflict resolution

### 4.8 eCommerce Integration (Optional)

**Online Store**
- Product catalog on web
- Shopping cart
- Checkout flow
- Order management
- Customer accounts

**Integration with ERP**
- Real-time inventory sync
- Order fulfillment from ERP
- Unified customer database
- Unified reporting

### 4.9 Fleet & Telematics Management (Optional/Domain-Specific)

**Vehicle/Asset Management**
- Vehicle/asset registration
- Vehicle details (make, model, year, VIN, registration)
- Vehicle ownership and transfer
- Vehicle service history (centralized across all branches)
- Vehicle warranty tracking
- Vehicle insurance tracking
- Vehicle documents (registration, insurance, etc.)

**Maintenance Management**
- Preventive maintenance scheduling
- Service reminders based on mileage/date
- Maintenance checklists
- Maintenance history and records
- Meter readings (odometer, engine hours)

**Fleet Analytics**
- Fleet utilization reports
- Maintenance cost analysis
- Vehicle performance metrics
- Telematics data integration (GPS, fuel, etc.)

**Appointments & Bay Scheduling** (for service centers)
- Appointment booking (online and manual)
- Bay/service bay management
- Technician assignment
- Appointment reminders (SMS/email)
- Appointment status tracking

**Job Cards & Workflows**
- Job card creation from appointment
- Service tasks and checklists
- Parts consumption tracking
- Labor time tracking
- Job status workflow (pending → in-progress → quality check → completed)
- Digital vehicle inspection reports
- Customer approval for additional work
- Job completion and invoicing

### 4.10 Manufacturing & Warehouse Operations (Optional)

**Manufacturing**
- Bill of Materials (BOM) management
- Work orders
- Production planning
- Production execution and tracking
- Raw material consumption
- Finished goods receipt
- Quality control

**Warehouse Management**
- Warehouse layouts and zones
- Bin/location management
- Put-away strategies
- Picking strategies (FIFO, zone picking)
- Packing and shipping
- Cycle counting
- Warehouse transfers

### 4.11 Reporting, Analytics & Dashboards

**Dashboard System**
- Role-based dashboards
- Customizable widget-based dashboards
- Real-time KPI displays
- Drill-down capabilities

**Standard Reports**
- Sales reports (by period, product, customer, branch)
- Inventory reports (stock levels, movements, valuation)
- Purchase reports (by supplier, product)
- Financial reports (P&L, balance sheet, cash flow)
- Tax reports
- Customer reports (segmentation, lifetime value)
- User activity reports

**Custom Report Builder**
- User-friendly report builder UI
- Filter and grouping options
- Calculated fields
- Report scheduling (email delivery)
- Export formats (PDF, Excel, CSV)

**Analytics & KPIs**
- Revenue trends
- Sales growth rate
- Inventory turnover
- Customer acquisition cost
- Customer lifetime value
- Gross margin analysis
- Cash flow analysis
- Top products/customers

### 4.12 Notifications & Alerts

**Notification Channels**
- In-app notifications
- Email notifications
- SMS notifications (Twilio, AWS SNS)
- Push notifications (web push, mobile)

**Notification Types**
- System alerts (errors, warnings)
- Business alerts (low stock, payment due, subscription expiry)
- User actions (approval requests, task assignments)
- Marketing notifications (campaigns, promotions)

**Notification Management**
- User notification preferences
- Notification templates
- Notification scheduling
- Notification history and read status

### 4.13 Integrations & APIs

**Integration Framework**
- Webhooks (outgoing events)
- Webhook consumers (incoming data)
- API rate limiting per tenant/user
- API versioning (v1, v2, etc.)
- API documentation (Swagger/OpenAPI)

**Third-Party Integrations**
- Payment gateways (Stripe, PayPal, etc.)
- Shipping providers (FedEx, UPS, DHL)
- Accounting software (QuickBooks, Xero)
- CRM systems (Salesforce, HubSpot)
- Email marketing (Mailchimp, SendGrid)
- SMS providers (Twilio, AWS SNS)

**Import/Export**
- CSV import for bulk data (products, customers, inventory)
- Excel import/export
- API-based bulk operations
- Import validation and error handling
- Background job processing for large imports

### 4.14 Logging, Auditing & Compliance

**Activity Logging**
- User activity logs (logins, actions)
- System event logs
- API access logs
- Data change logs (who, what, when)

**Audit Trails**
- Immutable audit records
- Before/after values for data changes
- Compliance-ready audit reports
- Retention policies for audit data

**Structured Logging**
- Correlation IDs for request tracing
- Contextual logging (tenant, user, request)
- Log levels (debug, info, warning, error, critical)
- Integration with centralized logging (ELK, CloudWatch)

**Compliance**
- GDPR compliance (data export, right to be forgotten)
- SOC 2 readiness
- Data retention policies
- Privacy policy and terms acceptance tracking

### 4.15 System Administration

**System Settings**
- Global system configuration
- Email server settings (SMTP)
- Payment gateway configuration
- Tax configuration
- Localization settings (timezone, date format)
- Currency settings
- Feature flags

**Tenant Administration**
- Tenant creation and management
- Subscription management
- Usage monitoring
- Tenant-level settings override

**User Administration**
- User management (create, update, delete, suspend)
- Role and permission management
- Bulk user operations
- User impersonation for support

**Database Administration**
- Database backup and restore
- Migration management
- Seeder execution
- Database optimization

**System Monitoring**
- Application health checks
- Performance metrics
- Error rates and alerts
- Queue monitoring
- Cache monitoring

---

## 5. Service-Layer Orchestration & Transaction Management

### Service Layer Responsibilities

**Business Logic Orchestration**
- All cross-module interactions MUST go through service layer
- No direct model-to-model calls across modules
- Services encapsulate business rules and workflows
- Services handle transaction boundaries

**Transaction Management**
- **Explicit Transactional Boundaries**
  - All service methods that modify data must define transaction scope
  - Use database transactions for atomicity
  - Ensure all-or-nothing behavior
  
- **Atomicity Guarantees**
  - Related operations succeed or fail together
  - No partial state changes
  - Proper rollback on exceptions
  
- **Idempotency**
  - Service operations should be idempotent where possible
  - Idempotency keys for duplicate prevention
  - Safe retries for failed operations
  
- **Consistent Exception Propagation**
  - Standardized exception handling across services
  - Meaningful error messages
  - Proper HTTP status codes for APIs
  - Transaction rollback on exceptions

**Rollback Safety**
- Database transactions ensure automatic rollback on failure
- Compensating transactions for distributed operations
- Event rollback mechanisms (optional)
- No orphaned data or inconsistent state

### Service Layer Patterns

**Example Service Structure**
```php
class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private InventoryService $inventoryService,
        private PaymentService $paymentService,
        private NotificationService $notificationService
    ) {}
    
    public function createOrder(CreateOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto) {
            // Step 1: Create order
            $order = $this->orderRepository->create($dto->toArray());
            
            // Step 2: Reserve inventory (via service)
            $this->inventoryService->reserveStock($order->items);
            
            // Step 3: Process payment (via service)
            $payment = $this->paymentService->processPayment($order->total, $dto->paymentMethod);
            
            // Step 4: Emit domain event (async notification)
            event(new OrderCreatedEvent($order));
            
            return $order;
        });
    }
}
```

---

## 6. Event-Driven Architecture

### Domain Events

**Purpose**
- Decouple modules via asynchronous communication
- Enable extensibility without modifying core code
- Support cross-cutting concerns (auditing, notifications, integrations)

**Event Types**
- User events (UserCreated, UserUpdated, UserDeleted)
- Tenant events (TenantCreated, SubscriptionChanged)
- Order events (OrderCreated, OrderShipped, OrderCancelled)
- Inventory events (StockMovement, LowStockAlert)
- Payment events (PaymentReceived, PaymentFailed)
- Custom business events

**Event Handling**
- Listeners for domain events
- Queued event listeners for async processing
- Event subscribers for multiple events
- Event replay for debugging

**Use Cases for Event-Driven Workflows**
1. **Notifications**
   - Send email/SMS when order is created
   - Alert admin when stock is low
   - Notify customer when payment fails
   
2. **Reporting & Analytics**
   - Update dashboard metrics on events
   - Aggregate data for reports
   
3. **CRM Automation**
   - Trigger marketing campaigns on customer actions
   - Update customer segments
   
4. **Integrations**
   - Sync data with external systems
   - Webhook dispatching to third parties
   
5. **Auditing**
   - Record all significant actions
   - Immutable audit trail

**Transaction Consistency**
- Events should NOT affect transactional consistency of core operations
- Events are dispatched AFTER successful transaction commit
- Event failures do not rollback core transaction
- Failed event handlers should be retryable

---

## 7. API Requirements

### RESTful API Design

**Versioning**
- URL-based versioning (e.g., `/api/v1/`, `/api/v2/`)
- Maintain backward compatibility
- Deprecation notices for old versions

**Endpoints Structure**
```
/api/v1/tenants
/api/v1/users
/api/v1/customers
/api/v1/products
/api/v1/inventory/stock-movements
/api/v1/orders
/api/v1/invoices
/api/v1/payments
```

**HTTP Methods**
- GET: Retrieve resource(s)
- POST: Create new resource
- PUT: Update entire resource
- PATCH: Partial update
- DELETE: Delete resource

**Request/Response Format**
- JSON for all requests and responses
- Consistent response structure:
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful",
  "meta": {
    "pagination": { ... },
    "filters": { ... }
  }
}
```

**Error Responses**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

**Status Codes**
- 200 OK: Successful GET, PUT, PATCH
- 201 Created: Successful POST
- 204 No Content: Successful DELETE
- 400 Bad Request: Invalid request
- 401 Unauthorized: Authentication required
- 403 Forbidden: Not authorized
- 404 Not Found: Resource doesn't exist
- 422 Unprocessable Entity: Validation errors
- 429 Too Many Requests: Rate limit exceeded
- 500 Internal Server Error: Server error

### API Features

**Pagination**
- Cursor-based pagination (preferred)
- Offset-based pagination (alternative)
- Page size limits (max 100 items per page)
- Meta information (total, current page, etc.)

**Filtering**
- Query parameter-based filtering
- Support for multiple operators (eq, ne, gt, lt, like, in)
- Relation-based filters

**Sorting**
- Multi-field sorting
- Ascending/descending order

**Field Selection (Sparse Fieldsets)**
- Allow clients to request specific fields
- Reduce payload size
- Example: `/api/v1/products?fields=id,name,price`

**Relation Loading**
- Eager loading of relations
- Nested relation loading
- Selective relation fields

**Bulk Operations**
- Bulk create/update/delete via API
- CSV import endpoints
- Background job processing for large operations
- Progress tracking for bulk operations

**Rate Limiting**
- Per-user/tenant rate limits
- Different limits for authenticated vs. anonymous
- Rate limit headers in response
- 429 status when limit exceeded

### API Documentation (Swagger/OpenAPI)

**Requirements**
- Auto-generated from code annotations
- Interactive API explorer (Swagger UI)
- Complete endpoint documentation
  - Endpoint description
  - Request parameters (path, query, body)
  - Request/response schemas
  - Authentication requirements
  - Example requests/responses
  - Status codes and error responses
- Versioned documentation per API version
- Export OpenAPI spec (JSON/YAML)

**Documentation Tools**
- L5-Swagger for Laravel
- PHPDoc annotations for endpoints
- Request/Response transformers documentation

---

## 8. Security Requirements

### Authentication Security

**Password Security**
- Minimum password requirements (8 characters, mixed case, numbers, symbols)
- Password hashing (bcrypt with cost factor 10+)
- Password history (prevent reuse of last 5 passwords)
- Password expiry policies (optional)
- Account lockout after N failed login attempts
- Brute-force protection (rate limiting)

**Token Security**
- Secure token generation (cryptographically strong)
- Token expiration (short-lived for security)
- Refresh tokens for long-lived sessions
- Token revocation support
- Token scopes and permissions

**Session Security**
- Secure session configuration
- HTTP-only cookies
- SameSite cookie attribute
- Session timeout (idle and absolute)
- Session fixation protection

**Multi-Factor Authentication**
- TOTP (Google Authenticator, Authy)
- SMS-based OTP
- Email-based OTP
- Backup codes for account recovery

### Authorization Security

**Access Control**
- Principle of least privilege
- All API endpoints protected by authentication
- Authorization checks on all operations
- Tenant isolation enforced at all layers
- Policy-based authorization

**Permission Checks**
- Controller-level permission checks
- Service-layer authorization
- Repository-level tenant scoping
- UI-level permission-aware rendering

### Data Security

**Encryption at Rest**
- Encrypted database fields for sensitive data (PII, payment info)
- Laravel's encrypted casting for model attributes
- Encrypted backups

**Encryption in Transit**
- HTTPS/TLS for all communications
- Strong cipher suites
- Certificate management (Let's Encrypt, etc.)

**Data Masking**
- Mask sensitive data in logs
- Mask PII in API responses (partial credit card, etc.)
- Data anonymization for non-production environments

### Input Validation & Sanitization

**Validation**
- Server-side validation for all inputs
- Laravel Form Requests for validation rules
- Type checking and constraints
- Business rule validation

**Sanitization**
- HTML sanitization to prevent XSS
- SQL injection prevention (Eloquent ORM, parameterized queries)
- Command injection prevention
- Path traversal prevention

### CSRF & XSS Protection

**CSRF (Cross-Site Request Forgery)**
- CSRF tokens for state-changing operations
- SameSite cookie attribute
- Verify CSRF token on all POST/PUT/DELETE

**XSS (Cross-Site Scripting)**
- Output escaping in templates (automatic in Blade)
- Content Security Policy (CSP) headers
- Input sanitization

### Rate Limiting

**API Rate Limiting**
- Per-user rate limits (e.g., 60 requests per minute)
- Per-tenant rate limits
- Per-IP rate limits for public endpoints
- Throttle middleware on routes
- Rate limit headers (X-RateLimit-Limit, X-RateLimit-Remaining)

**Login Rate Limiting**
- Limit login attempts per IP
- Account lockout after failed attempts
- CAPTCHA after N failed attempts

### Security Headers

**Required Security Headers**
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY` or `SAMEORIGIN`
- `X-XSS-Protection: 1; mode=block`
- `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- `Content-Security-Policy: ...`
- `Referrer-Policy: no-referrer-when-downgrade`

### Audit Trails

**Immutable Audit Logs**
- Log all significant actions (create, update, delete)
- Who performed the action (user ID)
- When it was performed (timestamp)
- What was changed (before/after values)
- Context information (IP, tenant, request ID)

**Audit Log Retention**
- Minimum retention period (e.g., 7 years for compliance)
- Audit log export for external storage
- Audit log integrity checks

### Compliance & Privacy

**GDPR Compliance**
- Data subject rights (access, rectification, erasure)
- Data export in machine-readable format
- Consent management
- Data retention policies
- Privacy policy and terms of service

**PCI DSS (if handling payment cards)**
- Do not store sensitive card data (CVV)
- Use payment gateway tokenization
- PCI-compliant hosting
- Regular security audits

**SOC 2 Readiness**
- Access controls and logging
- Incident response procedures
- Business continuity and disaster recovery
- Regular security assessments

---

## 9. Performance & Scalability Requirements

### Database Optimization

**Query Optimization**
- Use Eloquent query builder efficiently
- Avoid N+1 query problems (eager loading)
- Database indexes on frequently queried columns
- Composite indexes for multi-column queries
- Tenant-aware indexes

**Database Connection Pooling**
- Connection pooling for better resource utilization
- Read replicas for read-heavy workloads
- Database clustering for high availability

**Caching Strategies**
- Redis for application-level caching
- Tenant-aware cache keys
- Cache tags for grouped invalidation
- Query result caching
- Model caching (optional)
- Cache-aside pattern

### API Performance

**Response Time Targets**
- API response time < 200ms (95th percentile)
- Database query time < 50ms (95th percentile)
- Page load time < 2 seconds

**Optimization Techniques**
- Response compression (gzip)
- API response caching (GET requests)
- Pagination to limit result size
- Field selection to reduce payload
- Async processing for heavy operations

### Background Job Processing

**Queue System**
- Redis or RabbitMQ for job queues
- Multiple queue workers
- Priority queues for critical jobs
- Failed job handling and retry logic
- Job timeout configuration

**Job Types**
- Email sending
- Bulk imports/exports
- Report generation
- Data synchronization
- Scheduled tasks (daily/weekly reports)

### Horizontal Scaling

**Stateless Application Design**
- No server-side session storage (use database or Redis)
- Shared cache (Redis) across instances
- Shared file storage (S3, shared volume)

**Load Balancing**
- Application load balancer
- Session affinity (optional)
- Health checks for backend instances

**Database Scaling**
- Vertical scaling (increase resources)
- Horizontal scaling (read replicas)
- Database sharding (for massive scale)

### Monitoring & Alerting

**Application Monitoring**
- Prometheus for metrics collection
- Grafana for visualization
- Custom application metrics (API latency, queue depth, etc.)

**Alerts**
- High error rate
- Slow response time
- Queue backup
- Database connection issues
- Disk/memory usage

---

## 10. Testing Requirements

### Backend Testing

**Unit Tests**
- Test all service methods
- Test repository methods
- Test models and relationships
- Test helpers and utilities
- Target: >70% code coverage

**Feature/Integration Tests**
- Test API endpoints (request/response)
- Test authentication and authorization
- Test business workflows (order creation, payment processing, etc.)
- Test database transactions and rollbacks
- Test event dispatching

**Policy Tests**
- Test all authorization policies
- Test tenant isolation
- Test permission checks

### Frontend Testing

**Unit Tests**
- Test Vue components
- Test composables and utilities
- Test state management (Pinia stores)
- Target: >60% code coverage

**Component Tests**
- Test component behavior and interactions
- Test props and events
- Test conditional rendering

**E2E Tests**
- Test critical user flows (login, order placement, etc.)
- Test across different browsers
- Use Cypress or Playwright

### Testing Best Practices

**Test Structure**
- AAA pattern (Arrange, Act, Assert)
- Descriptive test names
- Isolated tests (no dependencies)
- Use factories and seeders for test data

**Test Data**
- Database seeding for tests
- Factory definitions for all models
- Fake data generators (Faker)

**Continuous Testing**
- Run tests on every commit (GitHub Actions)
- Automated testing in CI/CD pipeline
- Code coverage reporting
- Fail builds on test failures

---

## 11. Deployment & DevOps Requirements

### Docker Setup

**Development Environment**
- Docker Compose for local development
- Containers: app, database, Redis, queue worker, scheduler
- Hot reload for code changes
- Volume mounts for code
- Environment variable configuration

**Production Images**
- Multi-stage Docker builds
- Minimal base images (Alpine Linux)
- Security scanning for vulnerabilities
- Image tagging and versioning

### Kubernetes Deployment (Production)

**Resources**
- Deployments for application, workers, scheduler
- Services for load balancing
- ConfigMaps for configuration
- Secrets for sensitive data (database credentials, API keys)
- Persistent Volumes for storage

**Scaling**
- Horizontal Pod Autoscaler (HPA)
- Scale based on CPU/memory or custom metrics
- Min/max replica configuration

**Ingress & SSL**
- Ingress controller (NGINX, Traefik)
- SSL/TLS termination
- Certificate management (cert-manager)

**Monitoring & Logging**
- Prometheus for metrics
- Grafana for dashboards
- ELK or Loki for centralized logging
- Sentry for error tracking

### CI/CD Pipeline

**GitHub Actions Workflows**
- **Test Workflow**: Run on PR
  - Install dependencies
  - Run linters (PHPStan, ESLint)
  - Run backend tests
  - Run frontend tests
  - Code coverage report
  
- **Build Workflow**: Run on merge to main
  - Build Docker images
  - Push to container registry (Docker Hub, GitHub Registry, ECR)
  
- **Deploy Workflow**: Deploy to staging/production
  - Deploy to Kubernetes
  - Run smoke tests
  - Rollback on failure

### Infrastructure as Code (Optional)

**Terraform**
- Provision cloud infrastructure
- VPC, subnets, security groups
- Database instances (RDS, Cloud SQL)
- Redis clusters
- Load balancers
- S3 buckets

**Ansible** (Alternative)
- Server provisioning and configuration
- Application deployment
- Service management

### Backup & Disaster Recovery

**Database Backups**
- Automated daily backups
- Point-in-time recovery
- Backup retention (30 days minimum)
- Backup testing and restoration drills

**Application Backups**
- File storage backups (user uploads, documents)
- Configuration backups

**Disaster Recovery Plan**
- RPO (Recovery Point Objective): < 24 hours
- RTO (Recovery Time Objective): < 4 hours
- Documented recovery procedures
- Regular DR testing

---

## 12. Development Best Practices

### Code Quality

**Linting & Formatting**
- **PHP**: PHPStan (level 5+), PHP_CodeSniffer (PSR-12)
- **TypeScript**: ESLint with strict rules
- **Code Formatting**: Prettier for JS/TS, Laravel Pint for PHP
- Pre-commit hooks (Husky) for automatic linting

**Code Reviews**
- All code changes via Pull Requests
- At least one approval required
- Automated checks must pass
- Review checklist (functionality, tests, documentation)

**Documentation**
- PHPDoc for all public methods
- JSDoc for TypeScript functions
- Inline comments for complex logic
- README files for modules

### Git Workflow

**Branch Strategy**
- `main`: Production-ready code
- `develop`: Integration branch
- `feature/*`: Feature branches
- `bugfix/*`: Bug fix branches
- `hotfix/*`: Production hotfixes

**Commit Messages**
- Conventional commits format
- Example: `feat(inventory): add batch tracking`
- Types: feat, fix, docs, style, refactor, test, chore

**Pull Request Guidelines**
- Descriptive title and description
- Link to issue/ticket
- Include test results
- Add screenshots for UI changes

### Environment Configuration

**Environment Variables**
- `.env` for local development
- Separate `.env.example` with all keys (no values)
- Never commit `.env` file
- Use secrets management in production

**Configuration Files**
- Separate configs for different environments
- `config/database.php`, `config/cache.php`, etc.
- Feature flags for gradual rollouts

### Dependency Management

**Backend Dependencies**
- Composer for PHP dependencies
- Use stable versions (^x.y for minor updates)
- Regular dependency updates
- Security audits (composer audit)

**Frontend Dependencies**
- npm/yarn for JavaScript dependencies
- Use package-lock.json or yarn.lock
- Regular updates and security audits (npm audit)

**Dependency Selection Criteria**
- Prefer native framework features
- Use well-maintained, popular libraries
- Check license compatibility (MIT, Apache, etc.)
- Avoid experimental or abandoned packages

---

## 13. Specific Implementation Guidelines

### Laravel Implementation

**Project Structure**
```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Services/
├── Repositories/
│   ├── Contracts/  (interfaces)
│   └── Eloquent/   (implementations)
├── Models/
├── Events/
├── Listeners/
├── Jobs/
├── Policies/
├── DTOs/
└── Helpers/
```

**Service Provider Organization**
- `AppServiceProvider`: Basic bindings
- `AuthServiceProvider`: Policies
- `EventServiceProvider`: Event listeners
- `RepositoryServiceProvider`: Repository bindings
- Module-specific providers

**Middleware Stack**
- `TenantAwareMiddleware`: Set tenant context
- `EnsureEmailIsVerified`
- `CheckPermission`: Authorization
- `ThrottleRequests`: Rate limiting
- `Localization`: Set locale from request

**Eloquent Best Practices**
- Use accessors and mutators for data transformation
- Use attribute casting (`$casts`)
- Define relationships properly
- Use scopes for reusable queries
- Global scopes for tenant filtering

### Vue.js Implementation

**Project Structure**
```
resources/js/
├── components/
│   ├── common/
│   ├── layout/
│   └── feature-specific/
├── views/
├── stores/       (Pinia)
├── services/     (API clients)
├── composables/
├── router/
├── locales/      (i18n)
├── types/        (TypeScript types)
└── utils/
```

**Component Organization**
- Composition API for all components
- `<script setup>` syntax
- Props validation with TypeScript
- Emit validation
- Composables for reusable logic

**State Management (Pinia)**
- Separate store per feature/module
- Async actions for API calls
- Getters for computed state
- Store composition for shared logic

**Routing**
- Lazy-loaded routes for code splitting
- Route guards for authentication/authorization
- Meta fields for permissions
- Breadcrumb generation from routes

**API Services**
- Axios interceptors for authentication (token injection)
- Centralized error handling
- Request/response transformers
- TypeScript interfaces for API responses

**Styling**
- Tailwind CSS utility classes
- Component-level scoped styles (rare)
- Design system/theme configuration
- Dark mode support (optional)

---

## 14. Migration from Existing Systems (Optional)

### Data Migration Strategy

**Assessment Phase**
- Audit existing system data
- Identify data quality issues
- Map old schema to new schema
- Define transformation rules

**Migration Tools**
- Laravel migration scripts
- Custom import commands
- CSV import utilities
- API-based data migration

**Migration Steps**
1. Master data (tenants, users, roles)
2. Reference data (products, customers, suppliers)
3. Transactional data (orders, invoices, payments)
4. Historical data (audit logs, reports)

**Data Validation**
- Validate migrated data against business rules
- Reconciliation reports
- User acceptance testing (UAT)

**Cutover Plan**
- Freeze old system
- Final data sync
- Switch DNS/traffic
- Monitor for issues
- Rollback plan

---

## 15. Training & Documentation

### User Documentation

**User Guides**
- Getting started guide
- Role-specific user guides (admin, manager, cashier, etc.)
- Feature-specific tutorials
- FAQ section
- Video tutorials

**API Documentation**
- Swagger/OpenAPI interactive docs
- API authentication guide
- API rate limiting and best practices
- Code examples in multiple languages (cURL, PHP, JavaScript)

### Developer Documentation

**Onboarding**
- Development environment setup
- Project structure overview
- Coding standards and conventions
- Git workflow

**Architecture Documentation**
- System architecture diagrams
- Module descriptions
- Database schema diagrams (ERD)
- Sequence diagrams for workflows
- API architecture

**Maintenance Documentation**
- Deployment procedures
- Backup and restore procedures
- Troubleshooting guide
- Performance tuning guide

---

## 16. Success Criteria & Quality Metrics

### Functional Completeness

- [ ] All required modules implemented
- [ ] All API endpoints documented and working
- [ ] All user stories/requirements satisfied
- [ ] Multi-tenancy fully functional
- [ ] RBAC/ABAC authorization working
- [ ] Integrations tested and verified

### Code Quality Metrics

- **Test Coverage**: >70% backend, >60% frontend
- **Code Quality Score**: A rating (Code Climate, SonarQube)
- **Technical Debt Ratio**: <5%
- **Linting**: Zero linting errors
- **Security**: Zero critical vulnerabilities (Snyk, npm audit)

### Performance Metrics

- **API Response Time**: <200ms (95th percentile)
- **Page Load Time**: <2 seconds (first contentful paint)
- **Database Query Time**: <50ms (95th percentile)
- **Uptime**: 99.9% SLA

### Security Metrics

- **Security Audits**: Passed external security audit
- **Penetration Testing**: No critical/high vulnerabilities
- **Compliance**: GDPR/SOC 2 compliant

### User Experience Metrics

- **User Satisfaction**: >4.0/5.0 rating
- **Task Completion Rate**: >90%
- **Support Tickets**: <2% of users require support
- **Onboarding Time**: <30 minutes for new users

---

## 17. Related Repositories & References

AutoERP consolidates best practices from these repositories:

1. **AutoERP**: https://github.com/kasunvimarshana/AutoERP
   - TypeScript adoption
   - Domain specialization (vehicle service centers)
   - Production metrics and testing

For detailed analysis of each repository, see [ANALYSIS_SUMMARY.md](ANALYSIS_SUMMARY.md).

---

## 18. Version History

| Version | Date | Description |
|---------|------|-------------|
| 1.0 | 2026-02-02 | Initial consolidated requirements document |

---

## 19. Glossary

**ABAC**: Attribute-Based Access Control - authorization based on attributes  
**API**: Application Programming Interface  
**BOM**: Bill of Materials  
**CRUD**: Create, Read, Update, Delete  
**DTO**: Data Transfer Object  
**ERP**: Enterprise Resource Planning  
**FEFO**: First Expired, First Out  
**FIFO**: First In, First Out  
**GDPR**: General Data Protection Regulation  
**GST**: Goods and Services Tax  
**i18n**: Internationalization  
**IAM**: Identity and Access Management  
**KPI**: Key Performance Indicator  
**LTS**: Long-Term Support  
**MFA**: Multi-Factor Authentication  
**ORM**: Object-Relational Mapping  
**PAT**: Personal Access Token  
**PCI DSS**: Payment Card Industry Data Security Standard  
**PII**: Personally Identifiable Information  
**POS**: Point of Sale  
**RBAC**: Role-Based Access Control  
**RPO**: Recovery Point Objective  
**RTO**: Recovery Time Objective  
**SaaS**: Software as a Service  
**SKU**: Stock Keeping Unit  
**SOLID**: Single responsibility, Open-closed, Liskov substitution, Interface segregation, Dependency inversion  
**SSO**: Single Sign-On  
**TOTP**: Time-based One-Time Password  
**UAT**: User Acceptance Testing  
**UoM**: Unit of Measure  
**VAT**: Value Added Tax  
**XSS**: Cross-Site Scripting

---

**Document Maintainer**: AutoERP Development Team  
**Last Updated**: 2026-02-02  
**Status**: Living Document (subject to updates)
