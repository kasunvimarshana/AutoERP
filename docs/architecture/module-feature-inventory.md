# AutoERP Module Feature Inventory

Source of truth: `app/Modules`.
Supporting context reviewed: `RULES.md`, `README.md`, `docs/MODULE-PATTERN.md`, and `tmp/module-status.md`.

## Executive Summary

The codebase currently contains 28 modules under `app/Modules`. All runtime modules follow a consistent modular-monolith pattern: `Domain`, `Application`, `Infrastructure`, `Presentation`, and `routes/api.php`. The dominant implementation style is generated CRUD over Eloquent models through Core repository and Result abstractions. Only a few modules expose richer application workflows: `Auth`, `Tenant`, and `Configuration`.

Core is the shared architectural foundation. Every business/runtime module depends on `Core`; only a small set has explicit module-to-module imports in PHP code. Most business relationships are represented through database identifiers and validation rules rather than direct service calls between modules.

## Global Structure

| Module | Path | Tables | Runtime Surface | Primary Shape |
|---|---:|---:|---|---|
| Audit | `app/Modules/Audit` | 1 | Routes, controller, services, repository, model | Audit log CRUD |
| Auth | `app/Modules/Auth` | 9 | Auth workflow routes, providers, services, repositories | Authentication/session/token workflow |
| Configuration | `app/Modules/Configuration` | 5 | Configuration CRUD/cache plus reference-data CRUD | System configuration and locale metadata |
| Core | `app/Modules/Core` | 0 | Contracts, DTOs, Result, repository base, middleware, services | Shared architecture foundation |
| Customer | `app/Modules/Customer` | 4 | CRUD resources | Customer master data |
| Extension | `app/Modules/Extension` | 3 | CRUD resources | Attachments, comments, entity attributes |
| Finance | `app/Modules/Finance` | 18 | CRUD resources | Accounting, tax, budget, bank records |
| HR | `app/Modules/HR` | 27 | CRUD resources | Employees, attendance, leave, payroll, performance |
| Inventory | `app/Modules/Inventory` | 19 | CRUD resources | Stock, movement, transfer, traceability |
| Invoice | `app/Modules/Invoice` | 3 | CRUD resources | Invoices, references, lines |
| Item | `app/Modules/Item` | 11 | CRUD resources | Item catalog, variants, attributes, combos |
| OrganizationUnit | `app/Modules/OrganizationUnit` | 5 | CRUD resources | Organization hierarchy and settings |
| Payment | `app/Modules/Payment` | 9 | CRUD resources | Payments, allocations, cash/check records |
| Pricing | `app/Modules/Pricing` | 4 | CRUD resources | Price lists and party assignments |
| Purchase | `app/Modules/Purchase` | 6 | CRUD resources | Purchase order, GRN, return records |
| Sales | `app/Modules/Sales` | 6 | CRUD resources | Sales order, GDN, return records |
| Sequence | `app/Modules/Sequence` | 1 | CRUD resources | Document/reference sequences |
| Supplier | `app/Modules/Supplier` | 5 | CRUD resources | Supplier master data |
| SystemUser | `app/Modules/SystemUser` | 1 | CRUD resources | System-user bridge records |
| Tenant | `app/Modules/Tenant` | 6 | Tenant lifecycle plus CRUD resources | Tenant plans, domains, settings, documents |
| UOM | `app/Modules/UOM` | 2 | CRUD resources | Units of measure and conversions |
| User | `app/Modules/User` | 9 | CRUD resources | Users, roles, permissions, tenants, devices |
| Vehicle | `app/Modules/Vehicle` | 2 | CRUD resources | Vehicle master data and documents |
| VehicleRental | `app/Modules/VehicleRental` | 8 | CRUD resources | Lessor/lessee agreements and running charts |
| VehicleService | `app/Modules/VehicleService` | 10 | CRUD resources | Service types, job cards, labor, diagnostics, inspections |
| Voucher | `app/Modules/Voucher` | 2 | CRUD resources | Vouchers and recurring vouchers |
| Warehouse | `app/Modules/Warehouse` | 2 | CRUD resources | Warehouses and locations |

## Per-Module Feature Inventory

### Audit

Purpose: Records auditable events scoped by tenant, organization unit, and user.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Audit Log Management | Create, list, retrieve, update, and delete audit log records. | `AuditLog` / `audit_logs` | `app/Modules/Audit` | `Core`; schema references `tenant_id`, `organization_unit_id`, `user_id` |

Business logic observed: generic CRUD services with Result-based error wrapping.

### Auth

Purpose: Handles authentication provider setup, identities, sessions, tokens, verification challenges, login attempts, and SSO-style authorization codes.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Provider Management | Stores auth provider configuration per tenant/org context. | `AuthProvider` / `auth_providers` | `app/Modules/Auth` | `Core` |
| Client Authorization | Stores clients and authorization codes for token exchange. | `AuthClient`, `AuthAuthorizationCode` | `app/Modules/Auth` | `Core` |
| Identity Management | Links external/internal provider identities to users. | `AuthIdentity` | `app/Modules/Auth` | `Core`, `User` |
| Session Management | Creates, lists, and revokes sessions. | `AuthSession` | `app/Modules/Auth` | `Core`, provider registry |
| Token Lifecycle | Issues, refreshes, validates, and revokes access/refresh tokens. | `AuthAccessToken`, `AuthRefreshToken` | `app/Modules/Auth` | `Core`, provider registry |
| Verification Challenge | Requests and verifies challenge records. | `AuthVerificationChallenge` | `app/Modules/Auth` | `Core`, provider registry |
| Login Attempt Control | Records successful/failed login attempts and lockout checks. | `AuthLoginAttempt` | `app/Modules/Auth` | `Core`, config key `module-auth.*` |
| Registration Workflow | Registers users through provider or creates a user through `UserServiceInterface`. | `AuthIdentity`, `User` | `app/Modules/Auth` | `User` application contract |

Business logic observed: `AuthWorkflowService` centralizes login, logout, registration, token issue/refresh/validation, session revocation, verification challenge, client authorization, code exchange, login-attempt recording, failure clearing, and lockout checks.

### Configuration

Purpose: Manages system configuration plus country, currency, language, and timezone reference data.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Configuration Entries | Set, update, list, retrieve, delete configuration entries by key. | `Configuration` / `system_configurations` | `app/Modules/Configuration` | `Core` |
| Configuration Cache | Clears configuration cache via dedicated endpoint. | `Configuration` | `app/Modules/Configuration` | `Core` |
| Countries | CRUD for country records. | `Country` / `countries` | `app/Modules/Configuration` | `Core` |
| Currencies | CRUD for currency records. | `Currency` / `currencies` | `app/Modules/Configuration` | `Core` |
| Languages | CRUD for language records. | `Language` / `languages` | `app/Modules/Configuration` | `Core` |
| Timezones | CRUD for timezone records. | `Timezone` / `timezones` | `app/Modules/Configuration` | `Core` |

Business logic observed: dedicated configuration DTOs and set/update/cache-clear services; reference-data resources use standard CRUD services.

### Core

Purpose: Provides shared architecture primitives and cross-cutting infrastructure.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Result/Error Contract | Shared success/failure response type for application services. | `Result`, `Error` | `app/Modules/Core` | None |
| Repository Foundation | Shared Eloquent repository with `find`, `list`, `page`, `create`, `update`, `delete`, `restore`, `exists`, and transactions. | `EloquentRepository`, `DataRecord`, `PagedResult` | `app/Modules/Core` | Laravel Eloquent |
| Context Access | Tenant, organization-unit, and user current-context contracts, DTOs, accessors, resolvers, and middleware. | `CurrentTenantContext`, `CurrentOrganizationUnitContext`, `CurrentUserContext` | `app/Modules/Core` | Laravel request/middleware |
| Common Services | Shared clock, UUID, slug, file storage, and password-hashing contracts/implementations. | service contracts and infrastructure services | `app/Modules/Core` | Laravel services |
| Domain Primitives | Base entity, aggregate root, domain events, value objects, and domain exceptions. | `Entity`, `AggregateRoot`, `TenantId`, `OrganizationUnitId`, `Uuid` | `app/Modules/Core` | None |

Business logic observed: no business tables; it is an architecture/support module.

### Customer

Purpose: Maintains customer master records, contacts, addresses, and customer vehicles.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Customer Management | CRUD for customer records. | `Customer` / `customers` | `app/Modules/Customer` | `Core`; schema references tenant, org, user, currency, AR account |
| Customer Contacts | CRUD for customer contact rows. | `CustomerContact` / `customer_contacts` | `app/Modules/Customer` | `Customer`, tenant/org |
| Customer Addresses | CRUD for customer address rows. | `CustomerAddress` / `customer_addresses` | `app/Modules/Customer` | `Customer`, `countries` |
| Customer Vehicles | CRUD for customer-to-vehicle association rows. | `CustomerVehicle` / `customer_vehicles` | `app/Modules/Customer` | `Customer`, `vehicles` |

Business logic observed: generic CRUD services.

### Extension

Purpose: Adds generic extensibility records for file attachments, comments, and entity attributes.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Attachments | CRUD for polymorphic attachment metadata. | `Attachment` / `attachments` | `app/Modules/Extension` | `Core`; schema has `attachable_id` |
| Comments | CRUD for polymorphic comments with author reference. | `Comment` / `comments` | `app/Modules/Extension` | `Core`; schema has `commentable_id`, `author_id` |
| Entity Attributes | CRUD for dynamic key/value attributes. | `EntityAttribute` / `entity_attributes` | `app/Modules/Extension` | `Core`; schema has `entity_id` |

Business logic observed: generic CRUD services; no shared polymorphic abstraction beyond module-level tables.

### Finance

Purpose: Provides accounting, fiscal calendar, tax, payable/receivable, bank, budget, and cost-center records.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Chart of Accounts | CRUD for hierarchical accounts. | `Account` / `accounts` | `app/Modules/Finance` | `Core`; currency, parent account |
| Fiscal Calendar | CRUD for fiscal years and fiscal periods. | `FiscalYear`, `FiscalPeriod` | `app/Modules/Finance` | `Core` |
| Payment Terms | CRUD for payment term master data. | `PaymentTerm` | `app/Modules/Finance` | `Core` |
| Tax Setup | CRUD for tax groups, rates, and rules. | `TaxGroup`, `TaxRate`, `TaxRule` | `app/Modules/Finance` | `Core`; accounts, item categories |
| AP/AR Transactions | CRUD for payable and receivable transaction records. | `ApTransaction`, `ArTransaction` | `app/Modules/Finance` | `Core`; parties, accounts, currency |
| Cost Centers | CRUD for hierarchical cost centers. | `CostCenter` | `app/Modules/Finance` | `Core` |
| Journal Entries | CRUD for journal headers and lines. | `JournalEntry`, `JournalEntryLine` | `app/Modules/Finance` | `Core`; accounts, fiscal periods, currencies, taxes |
| Budgets | CRUD for budgets and budget lines. | `Budget`, `BudgetLine` | `app/Modules/Finance` | `Core`; fiscal years, accounts, cost centers |
| Bank Accounting | CRUD for bank accounts, categorization rules, transactions, and reconciliations. | `BankAccount`, `BankCategoryRule`, `BankTransaction`, `BankReconciliation` | `app/Modules/Finance` | `Core`; accounts, currency |

Business logic observed: generic CRUD services; accounting posting/balancing behavior is not implemented in observed services.

### HR

Purpose: Manages organization workforce records, attendance, leave, payroll, and performance data.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| HR Master Data | CRUD for departments, designations, and employment types. | `Department`, `Designation`, `EmploymentType` | `app/Modules/HR` | `Core` |
| Employees | CRUD for employees, contacts, documents, and contracts. | `Employee`, `EmployeeContact`, `EmployeeDocument`, `EmployeeContract` | `app/Modules/HR` | `Core`; users, countries, currencies |
| Attendance Devices | CRUD for biometric device records. | `BiometricDevice` | `app/Modules/HR` | `Core` |
| Attendance and Shifts | CRUD for holidays, attendance logs, shifts, shift assignments, and attendance records. | `Holiday`, `AttendanceLog`, `Shift`, `ShiftAssignment`, `AttendanceRecord` | `app/Modules/HR` | `Core`; employees, shifts, devices |
| Leave Management | CRUD for leave types, policies, policy lines, allocations, and applications. | `LeaveType`, `LeavePolicy`, `LeavePolicyLine`, `LeaveAllocation`, `LeaveApplication` | `app/Modules/HR` | `Core`; employees, approver users |
| Salary Structures | CRUD for salary components, structures, lines, and employee assignments. | `SalaryComponent`, `SalaryStructure`, `SalaryStructureLine`, `EmployeeSalaryAssignment` | `app/Modules/HR` | `Core`; accounts |
| Payroll | CRUD for payroll runs, payslips, and payslip lines. | `PayrollRun`, `Payslip`, `PayslipLine` | `app/Modules/HR` | `Core`; employees, salary structures, journal entries |
| Performance | CRUD for performance cycles and reviews. | `PerformanceCycle`, `PerformanceReview` | `app/Modules/HR` | `Core`; employees, reviewers |

Business logic observed: generic CRUD services; payroll calculation, approval workflow, and attendance computation are not implemented in observed services.

### Inventory

Purpose: Maintains stock identities, balances, movements, reservations, transfers, counts, inspections, tasks, costs, and trace logs.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Stock Identity | CRUD for batches and serials. | `Batche`, `Serial` | `app/Modules/Inventory` | `Core`; items, variants, suppliers, locations |
| Valuation Configuration | CRUD for valuation setup records. | `ValuationConfig` | `app/Modules/Inventory` | `Core`; warehouse/item/location dimensions |
| Stock Balances | CRUD for stock level records. | `StockLevel` | `app/Modules/Inventory` | `Core`; items, warehouses, locations, UOM |
| Stock Movements | CRUD for movement ledger records. | `StockMovement` | `app/Modules/Inventory` | `Core`; items, warehouse/location, UOM |
| Cost Layers | CRUD for inventory cost layers. | `InventoryCostLayer` | `app/Modules/Inventory` | `Core`; items, warehouses, source transactions |
| Reservations | CRUD for stock reservation records. | `StockReservation` | `app/Modules/Inventory` | `Core`; items, warehouses |
| Transfers | CRUD for stock transfers and transfer lines. | `StockTransfer`, `StockTransferLine` | `app/Modules/Inventory` | `Core`; warehouses, items, UOM |
| Adjustments | CRUD for stock adjustments and lines. | `StockAdjustment`, `StockAdjustmentLine` | `app/Modules/Inventory` | `Core`; warehouses, items, movement references |
| Cycle Counts | CRUD for count headers and lines. | `CycleCountHeader`, `CycleCountLine` | `app/Modules/Inventory` | `Core`; warehouses, users, items |
| Transfer Orders | CRUD for transfer orders and lines. | `TransferOrder`, `TransferOrderLine` | `app/Modules/Inventory` | `Core`; warehouses, items, UOM |
| Traceability | CRUD for trace logs. | `TraceLog` | `app/Modules/Inventory` | `Core`; identifier/warehouse/location references |
| Inbound/Outbound Tasks | CRUD for receipt inspections, put-away tasks, and picking tasks. | `ReceiptInspection`, `PutAwayTask`, `PickingTask` | `app/Modules/Inventory` | `Core`; documents, movements, warehouses, users |

Business logic observed: generic CRUD services; stock balance updates, allocation, valuation, and task state transitions are not implemented in observed services.

### Invoice

Purpose: Stores invoice headers, external document references, and invoice lines.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Invoice Headers | CRUD for invoice records. | `Invoice` / `invoices` | `app/Modules/Invoice` | `Core`; parties, currency, tax group, accounts, journal entry |
| Invoice References | CRUD for invoice-to-document reference rows. | `InvoiceReference` | `app/Modules/Invoice` | `Core`; documents, currency, accounts |
| Invoice Lines | CRUD for invoice line records. | `InvoiceLine` | `app/Modules/Invoice` | `Core`; invoice, item, UOM, tax, account |

Business logic observed: generic CRUD services; invoice calculation/posting is not implemented in observed services.

### Item

Purpose: Manages item catalog, categories, brands, attributes, variants, combinations, and identifiers.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Category and Brand Trees | CRUD for item categories and brands. | `ItemCategory`, `ItemBrand` | `app/Modules/Item` | `Core` |
| Item Catalog | CRUD for item master records. | `Item` | `app/Modules/Item` | `Core`; UOM, tax group, finance accounts |
| Attribute Model | CRUD for attribute groups, attributes, and values. | `ItemAttributeGroup`, `ItemAttribute`, `ItemAttributeValue` | `app/Modules/Item` | `Core` |
| Variants | CRUD for variants, variant attributes, and variant attribute values. | `ItemVariant`, `ItemVariantAttribute`, `ItemVariantAttributeValue` | `app/Modules/Item` | `Core`; items and attributes |
| Combo Items | CRUD for component relationships between items. | `ComboItem` | `app/Modules/Item` | `Core`; items, variants, UOM |
| Item Identifiers | CRUD for identifier records linking items to variants/batches/serials. | `ItemIdentifier` | `app/Modules/Item` | `Core`; items, variants, inventory identifiers |

Business logic observed: generic CRUD services; variant generation/combo costing behavior is not implemented in observed services.

### OrganizationUnit

Purpose: Maintains tenant-scoped organization types, hierarchy, settings, and documents.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Organization Unit Types | CRUD for organization unit type records. | `OrganizationUnitType` | `app/Modules/OrganizationUnit` | `Core`, `Tenant` |
| Organization Units | CRUD for hierarchical organization units. | `OrganizationUnit` | `app/Modules/OrganizationUnit` | `Core`, `Tenant` |
| Unit Settings | CRUD for setting groups and settings. | `OrganizationUnitSettingGroup`, `OrganizationUnitSetting` | `app/Modules/OrganizationUnit` | `Core`, `Tenant` |
| Unit Documents | CRUD for organization unit document records. | `OrganizationUnitDocument` | `app/Modules/OrganizationUnit` | `Core`, `Tenant`, `User` |

Business logic observed: mostly generic CRUD; explicit imports reference `Tenant` and `User`.

### Payment

Purpose: Stores payment methods, payment groups, payments, allocations, cash registers, checks, advance payments, and write-offs.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Payment Setup | CRUD for payment methods and groups. | `PaymentMethod`, `PaymentGroup` | `app/Modules/Payment` | `Core`; accounts |
| Payments | CRUD for payment records. | `Payment` | `app/Modules/Payment` | `Core`; parties, methods, accounts, currency, journal entries |
| Allocations | CRUD for payment allocation records. | `PaymentAllocation` | `app/Modules/Payment` | `Core`; payments, documents |
| Cash Registers | CRUD for cash-register records. | `CashRegister` | `app/Modules/Payment` | `Core`; cash accounts |
| Checks | CRUD for check records. | `Check` | `app/Modules/Payment` | `Core`; parties, bank accounts |
| Advances | CRUD for advance payments and advance allocations. | `AdvancePayment`, `AdvancePaymentAllocation` | `app/Modules/Payment` | `Core`; parties, payments, documents |
| Write-Offs | CRUD for write-off records. | `WriteOff` | `app/Modules/Payment` | `Core`; documents, journal entries |

Business logic observed: generic CRUD services; allocation settlement and accounting posting behavior is not implemented in observed services.

### Pricing

Purpose: Manages price lists, price list items, and party-specific price list assignments.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Price Lists | CRUD for price list headers. | `PriceList` | `app/Modules/Pricing` | `Core`; currency |
| Price List Items | CRUD for item/variant/warehouse/UOM-specific prices. | `PriceListItem` | `app/Modules/Pricing` | `Core`; item, variant, warehouse, UOM |
| Supplier Pricing | CRUD for supplier-to-price-list assignments. | `SupplierPriceList` | `app/Modules/Pricing` | `Core`; suppliers |
| Customer Pricing | CRUD for customer-to-price-list assignments. | `CustomerPriceList` | `app/Modules/Pricing` | `Core`; customers |

Business logic observed: generic CRUD services; price selection and precedence resolution are not implemented in observed services.

### Purchase

Purpose: Stores procurement documents for purchase orders, goods receipt notes, and purchase returns.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Purchase Orders | CRUD for purchase order headers and lines. | `PurchaseOrder`, `PurchaseOrderLine` | `app/Modules/Purchase` | `Core`; suppliers, warehouses, currencies, price lists, tax groups, items |
| Goods Receipts | CRUD for GRN headers and lines. | `GrnHeader`, `GrnLine` | `app/Modules/Purchase` | `Core`; purchase orders, items, warehouses, inventory identifiers |
| Purchase Returns | CRUD for purchase return headers and lines. | `PurchaseReturn`, `PurchaseReturnLine` | `app/Modules/Purchase` | `Core`; original PO/GRN/invoice, items, warehouses |

Business logic observed: generic CRUD services; procurement workflow, receipt-to-inventory posting, and invoice generation are not implemented in observed services.

### Sales

Purpose: Stores sales documents for sales orders, goods delivery notes, and sales returns.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Sales Orders | CRUD for sales order headers and lines. | `SalesOrder`, `SalesOrderLine` | `app/Modules/Sales` | `Core`; customers, warehouses, currencies, price lists, tax groups, items |
| Goods Deliveries | CRUD for GDN headers and lines. | `GdnHeader`, `GdnLine` | `app/Modules/Sales` | `Core`; sales orders, items, warehouses, inventory identifiers |
| Sales Returns | CRUD for sales return headers and lines. | `SalesReturn`, `SalesReturnLine` | `app/Modules/Sales` | `Core`; original SO/GDN/invoice, items, warehouses |

Business logic observed: generic CRUD services; reservation, delivery posting, and invoice generation are not implemented in observed services.

### Sequence

Purpose: Stores tenant/org-scoped sequence definitions.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Sequence Management | CRUD for sequence records. | `Sequence` / `sequences` | `app/Modules/Sequence` | `Core`, `Tenant`, `OrganizationUnit` |

Business logic observed: generic CRUD services; atomic next-number generation is not implemented in observed services.

### Supplier

Purpose: Maintains supplier master records, contacts, addresses, vehicles, and item links.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Supplier Management | CRUD for supplier records. | `Supplier` | `app/Modules/Supplier` | `Core`; user, currency, AP account |
| Supplier Contacts | CRUD for supplier contact rows. | `SupplierContact` | `app/Modules/Supplier` | `Supplier` |
| Supplier Addresses | CRUD for supplier address rows. | `SupplierAddresses` | `app/Modules/Supplier` | `Supplier`, countries |
| Supplier Vehicles | CRUD for supplier-to-vehicle association rows. | `SupplierVehicle` | `app/Modules/Supplier` | `Supplier`, vehicles |
| Supplier Items | CRUD for supplier-to-item association rows. | `SupplierItem` | `app/Modules/Supplier` | `Supplier`, items, variants |

Business logic observed: generic CRUD services.

### SystemUser

Purpose: Stores system-user bridge records.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| System User Mapping | CRUD for system user records. | `SystemUser` / `system_users` | `app/Modules/SystemUser` | `Core`; schema references tenant, org, user |

Business logic observed: generic CRUD services.

### Tenant

Purpose: Manages tenants, plans, domains, tenant settings, and tenant documents.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Tenant Lifecycle | Create, list, retrieve, update, activate, suspend, and deactivate tenants. | `Tenant` | `app/Modules/Tenant` | `Core`, `Configuration`, `OrganizationUnit`, `User` |
| Tenant Plans | CRUD for tenant plan records. | `TenantPlan` | `app/Modules/Tenant` | `Core`; currency |
| Tenant Settings | CRUD for tenant setting groups and settings. | `TenantSettingGroup`, `TenantSetting` | `app/Modules/Tenant` | `Core` |
| Tenant Documents | CRUD for tenant documents. | `TenantDocument` | `app/Modules/Tenant` | `Core` |
| Tenant Domains | CRUD for tenant domain records. | `TenantDomain` | `app/Modules/Tenant` | `Core` |
| Logo Upload Payload Handling | Tenant controller converts uploaded `logo_path` into temp-path/original-name mutation payload fields. | `Tenant` | `app/Modules/Tenant` | Laravel upload handling |

Business logic observed: tenant status transitions are dedicated services; tenant delete is excluded from tenant resource route.

### UOM

Purpose: Maintains units of measure and conversion records.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Units of Measure | CRUD for UOM master records. | `UnitOfMeasure` / `unit_of_measures` | `app/Modules/UOM` | `Core` |
| UOM Conversions | CRUD for conversion rules between UOMs, optionally item-specific. | `UomConversion` / `uom_conversions` | `app/Modules/UOM` | `Core`; UOM, item |

Business logic observed: generic CRUD services; conversion calculation is not implemented in observed services.

### User

Purpose: Manages users, roles, permissions, tenant memberships, documents, and devices.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| User Management | CRUD for user records. | `User` | `app/Modules/User` | `Core`, `Tenant`, `OrganizationUnit` |
| Role and Permission Catalog | CRUD for roles and permissions. | `Role`, `Permission` | `app/Modules/User` | `Core` |
| Role Permissions | CRUD for role-to-permission rows. | `RolePermission` | `app/Modules/User` | `Core`; roles, permissions |
| User Roles | CRUD for user-to-role rows. | `UserRole` | `app/Modules/User` | `Core`; users, roles |
| User Permissions | CRUD for direct user permission rows. | `UserPermission` | `app/Modules/User` | `Core`; users, permissions |
| User Tenants | CRUD for user-to-tenant rows. | `UserTenant` | `app/Modules/User` | `Core`; users, roles |
| User Documents | CRUD for user document records. | `UserDocument` | `app/Modules/User` | `Core`; users |
| User Devices | CRUD for user device records. | `UserDevice` | `app/Modules/User` | `Core`; users |

Business logic observed: generic CRUD services plus an abstract user CRUD controller. Authorization evaluation is not implemented in observed services.

### Vehicle

Purpose: Maintains vehicle master records and vehicle documents.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Vehicle Management | CRUD for vehicle records. | `Vehicle` / `vehicles` | `app/Modules/Vehicle` | `Core`, `Tenant`, `OrganizationUnit` |
| Vehicle Documents | CRUD for vehicle document records. | `VehicleDocument` / `vehicle_documents` | `app/Modules/Vehicle` | `Core`; vehicles |

Business logic observed: generic CRUD services.

### VehicleRental

Purpose: Stores rental agreements and running charts for lessor and lessee flows, plus agreement credit/debit notes.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Lessor Agreements | CRUD for lessor agreement records. | `VehicleRentalLessorAgreement` | `app/Modules/VehicleRental` | `Core`; parties, vehicles, finance accounts |
| Lessee Agreements | CRUD for lessee agreement records. | `VehicleRentalLesseeAgreement` | `app/Modules/VehicleRental` | `Core`; parties, vehicles, finance accounts |
| Lessor Running Charts | CRUD for lessor running chart records. | `VehicleRentalLessorRunningChart` | `app/Modules/VehicleRental` | `Core`; vehicles, agreements, drivers |
| Lessee Running Charts | CRUD for lessee running chart records. | `VehicleRentalLesseeRunningChart` | `app/Modules/VehicleRental` | `Core`; vehicles, agreements, drivers |
| Lessor Notes | CRUD for lessor agreement credit and debit notes. | `VehicleRentalLessorAgreementCreditNote`, `VehicleRentalLessorAgreementDebitNote` | `app/Modules/VehicleRental` | `Core`; agreements, accounts |
| Lessee Notes | CRUD for lessee agreement credit and debit notes. | `VehicleRentalLesseeAgreementCreditNote`, `VehicleRentalLesseeAgreementDebitNote` | `app/Modules/VehicleRental` | `Core`; agreements, accounts |

Business logic observed: generic CRUD services; rental billing, deposit, insurance, damage, extension, and inspection flows shown in supporting diagrams are not present in this module's current tables.

### VehicleService

Purpose: Stores vehicle service setup, job cards, consumed inventory/labor/non-inventory lines, labor assignment, diagnostics, and inspections.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Service Type Hierarchy | CRUD for service type records. | `VehicleServiceType` | `app/Modules/VehicleService` | `Core`; parent service type |
| Job Cards | CRUD for vehicle service job card headers. | `VehicleServiceJobCard` | `app/Modules/VehicleService` | `Core`; customers, vehicles, warehouses, currency, price list, tax group |
| Job Card Parts | CRUD for inventory line records attached to job cards. | `VehicleServiceJobCardLine` | `app/Modules/VehicleService` | `Core`; job cards, items, warehouse/location, UOM, tax, account |
| Labor Items | CRUD for service labor item records. | `VehicleServiceLaborItem` | `app/Modules/VehicleService` | `Core`; job cards, item/combo item, UOM, tax, account |
| Non-Inventory Items | CRUD for non-inventory service charge records. | `VehicleServiceNonInventoryItem` | `app/Modules/VehicleService` | `Core`; job cards, UOM, tax, account |
| Labor Assignments | CRUD for labor assignment rows. | `VehicleServiceLaborAssignment` | `app/Modules/VehicleService` | `Core`; job cards, labor items, employees |
| Diagnostics | CRUD for diagnostics and diagnostic lines. | `VehicleServiceDiagnostic`, `VehicleServiceDiagnosticLine` | `app/Modules/VehicleService` | `Core`; job cards |
| Inspections | CRUD for inspections and inspection lines. | `VehicleServiceInspection`, `VehicleServiceInspectionLine` | `app/Modules/VehicleService` | `Core`; job cards |

Business logic observed: generic CRUD services; workflow, invoice generation, warranty claims, and stock consumption posting are not implemented in observed services.

### Voucher

Purpose: Stores vouchers and recurring voucher templates.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Voucher Management | CRUD for voucher records. | `Voucher` | `app/Modules/Voucher` | `Core`; parties, accounts, tax rates, journal entries |
| Recurring Vouchers | CRUD for recurring voucher records. | `RecurringVoucher` | `app/Modules/Voucher` | `Core`; parties, accounts, tax rates |

Business logic observed: generic CRUD services; recurring generation and posting to journal entries are not implemented in observed services.

### Warehouse

Purpose: Maintains warehouse master data and hierarchical warehouse locations.

| Feature | Description | Entities | Module Path | Dependencies |
|---|---|---|---|---|
| Warehouses | CRUD for warehouse records. | `Warehouse` / `warehouses` | `app/Modules/Warehouse` | `Core` |
| Warehouse Locations | CRUD for warehouse location records. | `WarehouseLocation` / `warehouse_locations` | `app/Modules/Warehouse` | `Core`; warehouses, parent location |

Business logic observed: generic CRUD services.

## Global Feature Map

### Shared Services and Reusable Components

| Shared Component | Location | Used By | Observed Role |
|---|---|---|---|
| `Result` / `Error` | `Core\Application\Results` | Application services across modules | Standard success/failure contract |
| `DataRecord` / `PagedResult` | `Core\Application\DTO` | Repositories, list services, controllers | Generic read-model payloads and pagination |
| `EloquentRepository` | `Core\Infrastructure\Persistence\Eloquent\Repositories` | Most module repositories | Shared CRUD, paging, criteria filtering, transactions |
| Context accessors/middleware | `Core\Application\Contracts`, `Core\Presentation\Http\Middleware` | Auth and tenant/org/user-scoped modules | Current tenant/org/user resolution |
| `CoreModel` | `Core\Infrastructure\Persistence\Eloquent\Models` | Module Eloquent models | Shared guarded ID and casts for `metadata`, `row_version` |
| `FileStorageServiceInterface` | `Core\Application\Contracts` | Tenant-related file/logo handling potential | Shared storage boundary |
| `PasswordHasherInterface` | `Core\Application\Contracts` | Auth/User potential | Shared password hashing boundary |

### Explicit PHP Module Dependencies

| Module | Explicit PHP Dependencies |
|---|---|
| `Audit` | `Core` |
| `Auth` | `Core`, `User` |
| `Configuration` | `Core` |
| `Customer` | `Core` |
| `Extension` | `Core` |
| `Finance` | `Core` |
| `HR` | `Core` |
| `Inventory` | `Core` |
| `Invoice` | `Core` |
| `Item` | `Core` |
| `OrganizationUnit` | `Core`, `Tenant`, `User` |
| `Payment` | `Core` |
| `Pricing` | `Core` |
| `Purchase` | `Core` |
| `Sales` | `Core` |
| `Sequence` | `Core`, `OrganizationUnit`, `Tenant` |
| `Supplier` | `Core` |
| `SystemUser` | `Core` |
| `Tenant` | `Configuration`, `Core`, `OrganizationUnit`, `User` |
| `UOM` | `Core` |
| `User` | `Core`, `OrganizationUnit`, `Tenant` |
| `Vehicle` | `Core`, `OrganizationUnit`, `Tenant` |
| `VehicleRental` | `Core` |
| `VehicleService` | `Core` |
| `Voucher` | `Core` |
| `Warehouse` | `Core` |

### Major Schema-Level Relationships

| Relationship Hub | Connected Modules / Tables |
|---|---|
| Tenant scope | Almost all business tables carry `tenant_id`; tenant plan/settings/domains/documents are in `Tenant` |
| Organization unit scope | Most operational tables carry `organization_unit_id`; hierarchy/settings are in `OrganizationUnit` |
| User identity | `User` supports Auth, Audit, HR approvers/reviewers, Inventory task actors, SystemUser |
| Finance accounts | Referenced by Customer/Supplier, Item, Payment, Voucher, VehicleRental, HR salary components, Invoice/Purchase/Sales lines |
| Currency | Referenced by Tenant plans/tenants, Customer/Supplier, Finance, Invoice, Pricing, Purchase/Sales, HR contracts |
| Items/UOM | Used by Inventory, Invoice, Pricing, Purchase, Sales, VehicleService, Supplier items |
| Warehouse/location | Used by Inventory, Pricing, Purchase, Sales, VehicleService |
| Party-like records | Customer and Supplier are separate modules; several modules use generic `party_id` without an observed shared Party module |
| Documents/invoices/payments | Invoice and Payment use `document_id`; Purchase/Sales use original invoice references; supporting diagrams mention a generic document engine, but no `Document` module exists under `app/Modules` |
| Vehicle | Used by Customer, Supplier, VehicleRental, VehicleService |

### Overlaps and Duplicated Domain Shapes

| Overlap | Modules | Observation |
|---|---|---|
| Customer/Supplier master data | `Customer`, `Supplier` | Similar contacts, addresses, vehicle links, currency/account fields; no shared Party abstraction in code |
| Sales/Purchase document structures | `Sales`, `Purchase` | Header/line/return structures are symmetric; no shared document base or line calculation abstraction observed |
| Invoice/Payment allocation | `Invoice`, `Payment` | Both point to document/accounting concepts; settlement workflow is not abstracted |
| Settings trees | `Tenant`, `OrganizationUnit`, `Configuration` | Multiple settings/configuration models with similar group/key/value concepts |
| Documents/attachments | `Tenant`, `User`, `Vehicle`, `Employee`, `OrganizationUnit`, `Extension` | Several module-specific document tables plus generic attachments |
| Hierarchies | `Finance`, `Item`, `Warehouse`, `OrganizationUnit`, `VehicleService`, `HR` | Repeated `parent_id` tree patterns without shared hierarchy behavior |
| Generic CRUD classes | Nearly all modules | Repeated create/list/get/update/delete service classes and controllers per resource |
| Stock/account posting references | `Inventory`, `Purchase`, `Sales`, `VehicleService`, `Finance` | Operational modules carry account/tax/warehouse references but no shared posting or stock movement orchestration is implemented |

## Architecture Intelligence Report

### DRY Violations

| Area | Evidence | Impact | Recommendation |
|---|---|---|---|
| CRUD service duplication | Most resources have five near-identical `Create`, `List`, `Get`, `Update`, `Delete` services. | Large generated surface, high maintenance cost for behavior changes. | Introduce a generic CRUD application service base or resource service template only where customization is absent. Keep custom services for modules like `Auth`, `Tenant`, `Configuration`. |
| Controller duplication | CRUD controllers repeat pagination, Result handling, resource mapping, and status-code logic. | Repeated bug-fix effort and inconsistent edge-case handling risk. | Add a small `CrudControllerSupport` trait/base helper in `Core\Presentation` for pagination and Result-to-response mapping. |
| Request validation repetition | `tenant_id`, `organization_unit_id`, `row_version`, `metadata`, and common FK rules repeat across many requests. | Validation drift and noisy request classes. | Add shared rule builders in Core, e.g. `TenantScopedRules`, `OptimisticLockRules`, `MetadataRules`. |
| Module-specific document tables plus attachments | Multiple document tables coexist with generic `attachments`. | Fragmented file/document strategy. | Define a document/attachment boundary: either standardize generic attachments or clarify when module-specific document records are required. |
| Customer/Supplier symmetry | Contacts, addresses, vehicles, currency/account fields repeat. | Party-related features may diverge. | Consider a Party/BusinessPartner abstraction if future workflows require one shared customer/supplier/person identity. |

### SOLID Improvements

| Principle | Current Risk | Improvement |
|---|---|---|
| Single Responsibility | `AuthWorkflowService` implements many use-case interfaces and workflows. | Split into cohesive services: login/session, token, verification, client authorization, registration. Keep shared private helpers in a small collaborator. |
| Open/Closed | Generic CRUD behavior is copied into many concrete classes. | Centralize extension points in Core so modules can override only specialized behavior. |
| Interface Segregation | Many one-method service interfaces exist, but generated CRUD interfaces add volume. | Retain interfaces at module boundaries, but collapse generic CRUD contracts into reusable typed contracts where no custom behavior exists. |
| Dependency Inversion | Most modules depend on repository interfaces correctly. | Preserve this; avoid adding direct Eloquent use outside `Infrastructure`. |
| Liskov/Substitution | Repositories return generic `DataRecord`, which reduces type specificity. | For business-critical workflows, introduce typed DTOs/read models where invariants matter. |

### Missing Abstractions

| Missing Abstraction | Why It Matters | Candidate Location |
|---|---|---|
| Party / Business Partner | `party_id` appears in finance/payment/invoice/voucher/rental while Customer and Supplier are separate. | New module only after concrete workflows demand shared party behavior. |
| Generic Document Engine | Diagrams reference documents, document items, links, workflows; current modules use invoice/payment/document IDs but no `Document` module exists. | Future `Document` module or reuse Invoice only if scope stays invoice-specific. |
| Posting Engine | Finance journal references exist across HR, Payment, Voucher, Invoice, Purchase/Sales, but no posting orchestration is present. | `Finance` application services, with contracts exposed carefully. |
| Inventory Movement Engine | Stock movements and source documents exist, but operational modules do not orchestrate stock mutations. | `Inventory` application service boundary. |
| Pricing Resolution Service | Price list tables exist, but no service selects effective price by party/item/warehouse/date. | `Pricing` application service. |
| Sequence Generator | Sequence records exist, but no atomic next-number service is visible in observed files. | `Sequence` application service with transaction/locking semantics. |
| Approval / Workflow Engine | HR leave, documents, payments, service/rental flows contain statuses but no shared transition engine. | Core workflow contract or module-local first, depending on immediate reuse. |

### Scalability Risks

| Risk | Modules Affected | Detail | Mitigation |
|---|---|---|---|
| Generic list criteria only supports equality/where-in/null | All CRUD modules through Core repository | Filtering, sorting, search, authorization scoping, and complex indexes are not modeled in the generic repository. | Introduce query objects/specifications per high-volume resource. |
| Tenant/org scoping relies on payload and validation patterns | Most modules | Repeated rules increase risk of missing tenant/org filters in repositories. | Enforce tenant/org constraints in repository query decorators or global scopes where safe. |
| No observed optimistic concurrency enforcement | Most modules have `row_version` but updates call `fill/save`. | Concurrent writes may overwrite each other. | Add row-version compare-and-increment support in Core repository or module services. |
| High table count with generated endpoints | HR, Finance, Inventory, Item | Runtime API surface is large; cross-resource invariants are not protected by workflow services. | Promote aggregate-level use cases for operations that must update multiple tables. |
| Accounting and stock operations are record-level CRUD | Finance, Inventory, Purchase, Sales, Payment, VehicleService | Direct CRUD allows invalid business state unless database constraints cover it. | Add command services for posting, movement, allocation, reversal, cancellation. |
| Polymorphic references are stored as IDs without strong module contracts | Extension, Invoice, Payment, Inventory | `document_id`, `reference_id`, `attachable_id`, `entity_id` lack compile-time or service-level validation. | Add reference resolver contracts or document registry when cross-module workflows are implemented. |

### Modular Reuse Opportunities

| Opportunity | Reuse Target | Candidate Implementation |
|---|---|---|
| CRUD resource scaffolding | All generated CRUD modules | Core generic service/controller support with extension hooks |
| Tenant/org/user context | All tenant-scoped modules | Central query scoping and validation rule builders |
| Settings/configuration | Tenant, OrganizationUnit, Configuration | Shared setting value object, setting group behavior, and lookup service |
| Attachments/documents | Extension plus module document tables | Shared attachment/document metadata service |
| Accounting account references | Finance plus operational modules | Finance account validation and posting contracts |
| Item/UOM/warehouse references | Item, UOM, Warehouse, Inventory, Purchase/Sales, VehicleService | Shared catalog/inventory reference validators |
| Status transitions | Tenant, Auth, HR, Purchase/Sales, Inventory, VehicleService | Small workflow/state transition contract after first non-trivial workflow implementation |

## Implementation Reality Check

The current codebase is structurally broad and consistent, but most modules expose CRUD over records rather than domain workflows. That is useful for early data management and admin screens, but it does not yet enforce many ERP invariants in application code: posting, allocation, costing, stock movement, pricing resolution, approval, and document lifecycle are mostly represented by schema fields and relationships rather than implemented service behavior.

Recommended roadmap order:

1. Stabilize Core CRUD response, validation, tenant scoping, and row-version behavior.
2. Add workflow services only around high-risk business operations: auth/session, tenant lifecycle, accounting posting, inventory movement, invoice/payment allocation.
3. Introduce shared abstractions only when at least two modules need the same behavior immediately.
4. Keep migrations as the structural contract; adapt services to the existing schema unless a documented scalability or integrity issue requires schema change.
