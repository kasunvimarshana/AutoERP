# AGENT.md

## Architectural Mandate

This ERP SaaS is strictly modular.
Every functional capability must exist inside a Module.
No feature may exist in the root application.

---

## Clean Architecture Enforcement

Controllers:
- Validation only
- Authorization only
- Call Application Services

Application Services:
- Business logic only
- Dispatch events

Repositories:
- Data access only
- No business logic

Domain:
- Entities
- Value Objects
- Enums
- Domain Events

---

## Module Isolation Rules

- No cross-module model imports.
- No direct DB access outside repositories.
- No shared mutable state.
- No circular dependencies.
- Modules communicate via Events or Contracts only.

---

## Tenancy Rules

Every persistent entity MUST contain:
- tenant_id
- organization_id (if applicable)

All queries MUST be tenant-scoped.

---

## Security Rules

- Stateless JWT.
- Token per user × device × organization.
- Refresh token rotation.
- Token version invalidation.
- RBAC + ABAC via Policies and Middleware.
- Idempotency keys for critical APIs.

---

## Financial Integrity

- Use DECIMAL(18,8).
- No floats.
- Use BCMath.
- All writes in DB transactions.
- Use pessimistic locking for stock deduction.
- Optimistic locking via version column.
- Maintain audit logs.

---

## Metadata Rules

- Pricing rules stored in DB.
- Workflows defined in DB.
- UI rendered from metadata.
- No hardcoded pricing/workflow logic.

---

## Event-Driven Rules

- Modules emit events.
- Listeners handle side effects.
- No direct module-to-module invocation.

---

## Testing Requirements

Each module must include:
- Unit tests
- Feature tests
- Policy tests
- Concurrency tests

---

## Prohibited

- Business logic in controllers.
- Direct SQL in controllers.
- Hardcoded business rules.
- Cross-layer leakage.
- Global state.

---

## Required

- Clean Architecture.
- DDD.
- SOLID.
- DRY.
- KISS.
- API-first.
- Fully stateless.
- Fully modular.
- Production-ready.

## REFERENCES

These references provide guidance on modular design, Laravel best practices, multi-tenancy, ERP/CRM design, and other principles relevant to this repository:

- https://blog.cleancoder.com/atom.xml 
- https://en.wikipedia.org/wiki/Modular_design 
- https://en.wikipedia.org/wiki/Plug-in_(computing) 
- https://laravel.com/blog/building-a-multi-tenant-architecture-platform-to-scale-the-emmys 
- https://en.wikipedia.org/wiki/Enterprise_resource_planning 
- https://dev.to/rafaelogic/building-a-polymorphic-translatable-model-in-laravel-with-autoloaded-translations-3d99 
- https://sevalla.com/blog/building-modular-systems-laravel 
- https://github.com/laravel/laravel 
- https://laravel.com/docs/12.x/packages 
- https://swagger.io 
- https://en.wikipedia.org/wiki/SOLID 
- https://laravel.com/docs/12.x/filesystem 
- https://laravel-news.com/uploading-files-laravel 
- https://adminlte.io 
- https://tailwindcss.com 
- https://dev.to/bhaidar/understanding-database-locking-and-concurrency-in-laravel-a-deep-dive-2k4m 
- https://laravel-news.com/managing-data-races-with-pessimistic-locking-in-laravel 
- https://dev.to/tegos/pessimistic-optimistic-locking-in-laravel-23dk 
- https://dev.to/takeshiyu/handling-decimal-calculations-in-php-84-with-the-new-bcmath-object-api-442j 
- https://dev.to/keljtanoski/modular-laravel-3dkf 
- https://laravel.com/docs/12.x/processes 
- https://laravel.com/docs/12.x/helpers#pipeline 
- https://dev.to/preciousaang/multi-guard-authentication-with-laravel-12-1jg3 
- https://laravel.com/docs/12.x/authentication 
- https://laravel.com/docs/12.x/localization 
- https://sevalla.com/blog/building-modular-systems-laravel 
- https://www.laravelpackage.com 
- https://laravel-news.com/building-your-own-laravel-packages 
- https://dev.to/rafaelogic/building-a-polymorphic-translatable-model-in-laravel-with-autoloaded-translations-3d99 
- https://freek.dev/1567-pragmatically-testing-multi-guard-authentication-in-laravel 
- https://laravel-news.com/laravel-gates-policies-guards-explained 
- https://dev.to/codeanddeploy/how-to-create-a-custom-dynamic-middleware-for-spatie-laravel-permission-2a08 
- https://laravel.com/docs/12.x/authorization 
- https://en.wikipedia.org/wiki/Inventory 
- https://en.wikipedia.org/wiki/Inventory_management_software 
- https://en.wikipedia.org/wiki/Inventory_management 
- https://en.wikipedia.org/wiki/Inventory_management_(business) 
- https://en.wikipedia.org/wiki/Inventory_theory 
- https://en.wikipedia.org/wiki/Enterprise_resource_planning 
- https://en.wikipedia.org/wiki/SAP_ERP 
- https://en.wikipedia.org/wiki/SAP 
- https://en.wikipedia.org/wiki/E-commerce 
- https://en.wikipedia.org/wiki/Types_of_e-commerce 
- https://en.wikipedia.org/wiki/Headless_commerce 
- https://en.wikipedia.org/wiki/Barcode 
- https://en.wikipedia.org/wiki/QR_code 
- https://en.wikipedia.org/wiki/GS1 
- https://en.wikipedia.org/wiki/Warehouse_management_system 
- https://en.wikipedia.org/wiki/Domain_inventory_pattern 
- https://en.wikipedia.org/wiki/Point_of_sale 
- https://www.oracle.com/apac/food-beverage/what-is-pos 
- https://en.wikipedia.org/wiki/Gap_analysis 
- https://en.wikipedia.org/wiki/Business_process_modeling 
- https://en.wikipedia.org/wiki/Business_process 
- https://en.wikipedia.org/wiki/Business_Process_Model_and_Notation 
- https://en.wikipedia.org/wiki/Process_design 
- https://en.wikipedia.org/wiki/Business_process_mapping 
- https://en.wikipedia.org/wiki/Workflow_pattern 
- https://en.wikipedia.org/wiki/Workflow 
- https://en.wikipedia.org/wiki/Workflow_application 
- https://www.researchgate.net/publication/279515140_Enterprise_Resource_Planning_ERP_Systems_Design_Trends_and_Deployment 
- https://medium.com/@bugfreeai/key-system-design-component-design-an-inventory-system-2e2befe45844 
- https://www.cockroachlabs.com/blog/inventory-management-reference-architecture 
- https://www.0xkishan.com/blogs/designing-inventory-mgmt-system 
- https://quickbooks.intuit.com/r/bookkeeping/complete-guide-to-double-entry-bookkeeping
- https://www.extension.iastate.edu/agdm/wholefarm/pdf/c6-33.pdf
- https://en.wikipedia.org/wiki/Double-entry_bookkeeping 
- https://en.wikipedia.org/wiki/Bookkeeping 
- https://github.com/DarkaOnLine/L5-Swagger 
- https://medium.com/@nelsonisioma1/how-to-document-your-laravel-api-with-swagger-and-php-attributes-1564fc11c305 
- https://medium.com/@harryespant/advanced-microservices-architecture-in-laravel-high-level-design-dependency-injection-repository-0e787a944e7f 
- https://dev.to/programmerhasan/creating-a-microservice-architecture-with-laravel-apis-3a16 
- https://laravel.com/ai/mcp 
- https://laravel.com/docs/12.x/mcp 
- https://dev.to/keljtanoski/modular-laravel-3dkf 
- https://github.com/L5Modular/L5Modular 
- https://github.com/nWidart/laravel-modules 
- https://github.com/keljtanoski/modular-laravel 
- https://laravel.com/docs/12.x 
- https://woocommerce.com 
- https://developer.wordpress.org/plugins/intro 
- https://en.wikipedia.org/wiki/WooCommerce 
- https://github.com/woocommerce/woocommerce 
- https://woocommerce.github.io/woocommerce-rest-api-docs/#introduction 
- https://developer.wordpress.org 
- https://github.com/Astrotomic/laravel-translatable 
- https://github.com/spatie/laravel-translatable 
- https://laravel.com/docs/12.x/localization 
- https://oneuptime.com/blog/post/2026-02-03-laravel-multi-language/view 
- https://github.com/spatie/laravel-translatable/blob/main/docs/introduction.md 
- https://dev.to/abstractmusa/modular-monolith-architecture-within-laravel-communication-between-different-modules-a5 
- https://oneuptime.com/blog/post/2026-02-02-laravel-database-transactions/view 
- https://laravel-news.com/database-transactions 
- https://github.com/spatie/laravel-multitenancy 
- https://laravel.com/blog/building-a-multi-tenant-architecture-platform-to-scale-the-emmys 
- https://github.com/archtechx/tenancy 
- https://sevalla.com/blog/mcp-server-laravel 
- https://github.com/laravel/mcp 
- https://tailadmin.com 
- https://github.com/TailAdmin/tailadmin-free-tailwind-dashboard-template 
- https://github.com/ColorlibHQ/AdminLTE 
- https://adminlte.io/blog/free-react-templates 
- https://madewithreact.com/reactjs-adminlte 
- https://github.com/TailAdmin/free-react-tailwind-admin-dashboard?tab=readme-ov-file 
