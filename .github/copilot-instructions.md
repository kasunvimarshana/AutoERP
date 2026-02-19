# Instructions

## Role and Responsibilities

## Core Principles

### Architecture Standards
- **Clean Architecture**: Enforce strict separation of concerns with clear boundaries between layers
- **Domain-Driven Design (DDD)**: Organize code around business domains with explicit domain boundaries
- **SOLID Principles**: Single responsibility, Open/closed, Liskov substitution, Interface segregation, Dependency inversion
- **DRY (Don't Repeat Yourself)**: Eliminate code duplication and redundancy
- **KISS (Keep It Simple, Stupid)**: Prefer simple, maintainable solutions over complex ones
- **API-First Development**: Design all functionality with API-first approach

### Modular Architecture
Strictly follow modular architecture principles:
- Design all modules as **fully isolated, loosely coupled, plugin-style components**
- Modules must be independently **installable, removable, extendable, or replaceable**
- **No circular dependencies** between modules
- **No shared state** across modules
- **No direct cross-module imports**
- Communication **only via explicit contracts, events, or APIs**
- All behavior and configuration must be **metadata-driven**
- Core provides abstractions only; modules implement concrete functionality
- Detect and implement all missing or incomplete modules
- Eliminate duplication, coupling, and architectural violations

### Technology Stack
- **Backend**: Native Laravel (version 12.x) capabilities only; avoid additional PHP/backend packages unless explicitly approved as core dependencies
- **Frontend Runtime**: Native Vue capabilities only for shipped/browser runtime code
- **Frontend Tooling Exceptions**: Frontend build-time tooling (for example Tailwind CSS, bundlers, linters) and API documentation tooling (for example Swagger/OpenAPI generators) are allowed when used strictly as development/build-time tools, not as required runtime dependencies
- **Manual Implementation**: Prefer manual implementation for backend/business logic instead of relying on third-party libraries; use external tools only where they clearly improve developer productivity without becoming hard runtime dependencies
- **Dependencies**: Only use unavoidable, stable, LTS, officially supported dependencies when absolutely essential; any third-party backend or runtime package must be explicitly justified, documented, and approved
- **Prohibited**: Experimental, deprecated, abandoned, placeholder, partial, or unsupported implementations and dependencies; reference links to third-party tools in this document are **informational, optional examples only**, not mandatory or endorsed dependencies

## System Architecture

### Multi-Tenancy and Organization Structure
- **Strict tenant isolation**: Ensure complete data and process separation between tenants
- **Hierarchical multi-level organizational structures**: Support nested organizations with inheritance
- **Distributed architecture**: Design for scalability across multiple nodes and services

### Authentication and Authorization
- **Stateless Application**: No server-side sessions; fully stateless at the application layer
- **JWT Authentication**: Token-based authentication per user × device × organization
- **Secure Token Lifecycle**: Proper token generation, validation, refresh, and revocation
- **Multi-Guard Support**: Support multiple authentication guards
- **Multi-User/Multi-Device Concurrency**: Handle concurrent access safely
- **RBAC (Role-Based Access Control)**: Implement via native Laravel policies and middleware
- **ABAC (Attribute-Based Access Control)**: Support attribute-based permissions via native Laravel policies

### Data Integrity and Concurrency
- **Database Transactions**: Use atomic transactions for all data modifications
- **Foreign Key Constraints**: Enforce referential integrity at database level
- **Optimistic Locking**: Implement versioning for concurrent updates
- **Pessimistic Locking**: Use database locks when necessary for critical sections
- **Retry-Safe APIs**: Design endpoints to be safely retryable; use idempotency keys for non-idempotent operations (e.g., POST create), and keep PUT/PATCH/DELETE semantics consistent
- **Audit Logging**: Comprehensive, structured audit trails for all critical operations
- **Deterministic Calculations**: All financial and quantity calculations must be precision-safe and auditable

### Event-Driven Architecture
- **Native Events**: Use Laravel's native event system
- **Queues**: Leverage Laravel's queue system for asynchronous processing
- **Processes**: Use Laravel's process management
- **Pipelines**: Implement workflow pipelines using Laravel's pipeline helper
- **Event/Contract-Only Integration**: Modules integrate only through events and contracts

### Dynamic and Metadata-Driven System
- **Runtime Configurable**: All behaviors, rules, pricing, and modules customizable without code changes
- **Metadata-Driven**: UI, workflows, permissions, rules, and configurations driven by metadata
- **Dynamic UI Rendering**: Generate UI components dynamically based on metadata
- **Centralized Configuration**: Define configuration in Laravel config files (accessed via `config()`), source environment-specific values from `.env`, and reserve enums for true domain constants (avoid spreading hardcoded literals)
- **Service Orchestration**: Scalable service orchestration for complex workflows

## Business Domain Implementation

### Product and Service Models
- **Dynamic Products**: Support for goods, services, bundles, and composite offerings
- **Multi-Unit Support**: Configurable buying and selling units
- **Location-Based Pricing**: Different prices per location
- **Flexible Product Types**: Product/Service/Combo models

### Pricing and Calculation Engines
- **Extensible Pricing Engines**: Support multiple pricing strategies (flat, percentage, tiered, rule-based)
- **Calculation Rules**: Implement flat, percentage, and tiered pricing logic
- **Rule-Driven Computations**: Dynamic, pluggable pricing rules
- **Precision-Safe**: Use BCMath for all financial and quantity calculations (deterministic and auditable)

## Development Standards

### Code Quality
- **Clean Code**: Write clean, readable, and self-documenting code
- **Meaningful Naming**: Use consistent and descriptive names for all entities
- **Production-Ready**: All code must be production-ready, never use placeholders or partial implementations
- **Documentation**: Maintain clear module-level documentation
- **No Partial Implementations**: Complete all features fully with no placeholders
- **Long-Term Sustainability**: Align all code with enterprise standards and long-term sustainability

### Security Requirements
- **Enterprise-Grade Security**: Implement security best practices throughout
- **Secure Data Handling**: Protect sensitive data at rest and in transit
- **Input Validation**: Validate all inputs thoroughly
- **Output Encoding**: Properly encode all outputs
- **No Secrets in Code**: Never commit secrets or sensitive data

### Scalability and Performance
- **Scalable Design**: Architecture must support horizontal and vertical scaling
- **Performance Optimization**: Optimize for performance without premature optimization
- **Fault Tolerance**: Design for resilience and graceful degradation
- **Caching Strategy**: Implement appropriate caching where beneficial
- **Concurrency-Safe Processing**: Ensure all operations are safe for concurrent execution

## Configuration Standards

### Environment Configuration
- **Config Files First**: Define application configuration in Laravel `config/` files and access it via `config()`
- **Environment Variables**: Use `.env` only for environment-specific, deploy-time values that feed into config files; avoid reading environment variables directly throughout the app
- **Domain Enums**: Use enums for true domain-level constants (business concepts), not for every configuration value
- **No Hardcoded Sensitive/Env Values**: Do not hardcode environment-specific or sensitive configuration values (such as secrets, credentials, or hostnames) in code; prefer configuration files backed by `.env`

### Module Detection and Implementation
- **Identify Missing Modules**: Detect all missing or incomplete modules
- **Implement According to Domain**: Follow documented domain boundaries
- **Fix Circular Dependencies**: Eliminate all circular dependencies
- **Remove Duplication**: Identify and remove code duplication

## Documentation Requirements

- **Update Documentation**: Keep all project documentation current
- **Align with Insights**: Reflect extracted knowledge and best practices
- **Clear Domain Boundaries**: Document module boundaries and responsibilities
- **Architecture Documentation**: Maintain comprehensive architecture documentation

## Reference Resources

### Architecture and Design Patterns
- [Clean Code Blog](https://blog.cleancoder.com/atom.xml)
- [Modular Design - Wikipedia](https://en.wikipedia.org/wiki/Modular_design)
- [Plugin Architecture - Wikipedia](https://en.wikipedia.org/wiki/Plug-in_(computing))
- [SOLID Principles - Wikipedia](https://en.wikipedia.org/wiki/SOLID)
- [Enterprise Resource Planning - Wikipedia](https://en.wikipedia.org/wiki/Enterprise_resource_planning)

### Laravel Resources
- [Laravel Official Repository](https://github.com/laravel/laravel)
- [Laravel 12.x Documentation - Packages](https://laravel.com/docs/12.x/packages)
- [Laravel 12.x Documentation - Filesystem](https://laravel.com/docs/12.x/filesystem)
- [Laravel 12.x Documentation - Processes](https://laravel.com/docs/12.x/processes)
- [Laravel 12.x Documentation - Pipeline Helper](https://laravel.com/docs/12.x/helpers#pipeline)
- [Laravel 12.x Documentation - Authentication](https://laravel.com/docs/12.x/authentication)
- [Laravel 12.x Documentation - Localization](https://laravel.com/docs/12.x/localization)
- [Building Multi-Tenant Architecture - Laravel](https://laravel.com/blog/building-a-multi-tenant-architecture-platform-to-scale-the-emmys)

### Laravel Best Practices and Patterns
- [Building Modular Systems in Laravel](https://sevalla.com/blog/building-modular-systems-laravel)
- [Modular Laravel](https://dev.to/keljtanoski/modular-laravel-3dkf)
- [Uploading Files in Laravel](https://laravel-news.com/uploading-files-laravel)
- [Managing Data Races with Pessimistic Locking](https://laravel-news.com/managing-data-races-with-pessimistic-locking-in-laravel)
- [Multi-Guard Authentication with Laravel 12](https://dev.to/preciousaang/multi-guard-authentication-with-laravel-12-1jg3)
- [Building Polymorphic Translatable Models](https://dev.to/rafaelogic/building-a-polymorphic-translatable-model-in-laravel-with-autoloaded-translations-3d99)

### Database and Concurrency
- [Understanding Database Locking and Concurrency in Laravel](https://dev.to/bhaidar/understanding-database-locking-and-concurrency-in-laravel-a-deep-dive-2k4m)
- [Pessimistic & Optimistic Locking in Laravel](https://dev.to/tegos/pessimistic-optimistic-locking-in-laravel-23dk)
- [Handling Decimal Calculations in PHP 8.4](https://dev.to/takeshiyu/handling-decimal-calculations-in-php-84-with-the-new-bcmath-object-api-442j)

### Frontend and UI
The following are **optional examples** of frontend or build-time tooling. They are not required or assumed to be pre-installed. You may use similar tools where appropriate, but avoid introducing new backend PHP runtime dependencies without explicit approval.
- [AdminLTE](https://adminlte.io) – optional admin UI/theme reference; treat as a design/example resource rather than a mandated package
- [Tailwind CSS](https://tailwindcss.com) – allowed as an optional build-time CSS utility framework if it fits the chosen frontend stack

### API Documentation
Tooling such as Swagger/OpenAPI may be used for **API design and documentation generation only** and does not imply any additional PHP runtime dependency. Treat these as optional documentation tools, not required libraries.
- [Swagger/OpenAPI](https://swagger.io)

### Reference Implementations
- [AutoERP](https://github.com/kasunvimarshana/AutoERP)

## Workflow Guidelines

### Before Implementation
1. **Audit and Review**: Thoroughly audit all existing documentation, code, schemas, and configurations
2. **Analyze Resources**: Extract concepts, patterns, and best practices from reference materials
3. **Build Conceptual Model**: Create complete conceptual and technical model
4. **Identify Gaps**: Detect all missing or incomplete modules
5. **Plan Architecture**: Design according to domain boundaries and relationships

### During Implementation
1. **Follow Standards**: Adhere strictly to all architectural and coding standards
2. **Use Native Features**: Rely exclusively on native Laravel and Vue features
3. **Implement Manually**: Build features manually instead of using third-party libraries
4. **Maintain Isolation**: Keep modules loosely coupled and independently deployable
5. **Test Thoroughly**: Ensure all functionality works as expected

### After Implementation
1. **Update Documentation**: Reflect all changes in documentation
2. **Validate Quality**: Ensure code meets all quality standards
3. **Security Review**: Verify security best practices are followed
4. **Performance Check**: Validate scalability and performance
5. **No Placeholders**: Ensure complete implementation with no placeholders

## Critical Rules

### Must Follow
✅ Use native Laravel and Vue features exclusively
✅ Implement features manually
✅ Maintain strict modular architecture
✅ Ensure complete tenant and organizational isolation
✅ Use enums and `.env` for all configuration
✅ Implement comprehensive audit logging
✅ Follow Clean Architecture and DDD principles
✅ Make all code production-ready
✅ Maintain clear documentation
✅ Ensure data integrity and concurrency safety

### Must Avoid
❌ Unapproved backend/server-side third-party runtime libraries or Laravel/PHP packages (frontend build-time UI tools like Tailwind/AdminLTE and API documentation tooling such as Swagger/OpenAPI are allowed as optional development-time or UI dependencies)
❌ Hardcoded values in code
❌ Circular dependencies between modules
❌ Shared state across modules
❌ Direct cross-module imports
❌ Server-side sessions (must be stateless)
❌ Placeholders or partial implementations
❌ Experimental, deprecated, or abandoned dependencies
❌ Security vulnerabilities
❌ Committing secrets to code
❌ Unsupported integrations
❌ Code duplication and redundancy

## Summary

Build a **multi-tenant, distributed, hierarchical multi-organization, enterprise-grade ERP/CRM SaaS platform** that is:
- **Fully modular**: Plugin-style, loosely coupled modules that can be independently installed, removed, replaced, or extended
- **Metadata-driven**: Runtime-configurable without code changes; all UI, workflows, permissions, rules, pricing, calculations, and module behaviors customizable via metadata
- **Stateless**: JWT authentication per user × device × organization, no server-side sessions
- **Secure**: Enterprise-grade security and data protection with comprehensive audit logging
- **Data Integrity**: Database transactions, foreign key constraints, optimistic and pessimistic locking, versioning, idempotent APIs
- **Scalable**: Designed for horizontal and vertical scaling with distributed architecture
- **Maintainable**: Clean, readable, well-documented, production-ready code
- **Fault-tolerant**: Resilient and gracefully degrading
- **Standards-compliant**: Following Clean Architecture, DDD, SOLID, DRY, KISS, and API-first principles
- **Event-driven**: Native events, queues, processes, pipelines for asynchronous workflows
- **Extensible**: Pluggable pricing engines, flexible product models, rule-driven computations

All implementations must use **native Laravel and Vue features only**, be **manually implemented**, use only **unavoidable stable LTS dependencies**, maintain **strict module isolation**, ensure **data integrity**, support **concurrent access**, eliminate **duplication and coupling**, and be **fully production-ready** with **no placeholders, partial implementations, or unsupported integrations**.
