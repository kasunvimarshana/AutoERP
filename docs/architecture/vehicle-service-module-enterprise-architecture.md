# Vehicle Service Module Enterprise Architecture

## Scope

The VehicleService module owns service job execution for vehicles: job intake, service categorization, employee allocation, parts consumption, labor/non-inventory charges, completion, invoicing, payments, incentives, and accounting integration.

Existing Eloquent migrations and models are the source of truth. The actual persisted job-card status values are:

- `open`
- `in_progress`
- `waiting_parts`
- `completed`
- `invoiced`
- `cancelled`

The requested conceptual flow `Pending -> In Progress -> Completed -> Delivered` maps to the implemented persistence flow as `open -> in_progress -> completed -> invoiced`.

VehicleRental is intentionally not used.

## Module Structure

```text
app/Modules/VehicleService
├── Application
│   ├── DTOs
│   └── Orchestrators
├── Domain
│   ├── Constants
│   ├── Events
│   └── Services
├── Infrastructure
│   ├── Config
│   ├── Listeners
│   ├── Persistence/Eloquent
│   │   ├── Migrations
│   │   ├── Models
│   │   └── Repositories
│   └── Providers
├── Presentation
│   └── Http
│       ├── Controllers
│       ├── Requests
│       └── Resources
└── routes
```

## Domain Entities And Relationships

- `vehicle_service_job_cards`
  - Aggregate root for one service job.
  - Links customer, vehicle, warehouse, service type, assigned supervisor, currency, totals, and status.

- `vehicle_service_job_card_lines`
  - Inventory/parts lines consumed during service.
  - Supports item, variant, warehouse, location, batch, serial, UOM, cost, tax, and discount fields.

- `vehicle_service_labor_items`
  - Billable labor/service lines.
  - Supports quantity, rate, tax, discounts, incentive type/value/amount.

- `vehicle_service_non_inventory_items`
  - Sundries, service charges, package fees, and other non-stock billable lines.

- `vehicle_service_labor_assignments`
  - Employee-to-labor assignment.
  - Supports role, hours worked, hourly rate, incentive configuration, and performance notes.

- `vehicle_service_types`
  - Service categorization hierarchy.

- `vehicle_service_diagnostics` and `vehicle_service_inspections`
  - Service quality and vehicle condition records.

## Service Breakdown

- `VehicleServiceLifecycleService`
  - Creates job cards with optional nested lines.
  - Starts jobs and dispatches vehicle status updates.
  - Completes jobs and dispatches inventory consumption.
  - Generates service invoices.
  - Records service payments and updates invoice/job balances.

- `VehicleServiceCalculationService`
  - Recalculates inventory, labor, and non-inventory lines.
  - Calculates discounts, taxes, totals, balances, and incentives.
  - Recalculates labor assignment incentives.

- `VehicleServiceOrchestrator`
  - Thin application facade for controller use.
  - Keeps controllers free of business logic.

Shared services:

- `InventoryTransactionService`
  - Issues inventory parts on job completion.
  - Prevents negative stock.

- `JournalEntryService`
  - Posts service invoice and payment entries.

- `SequenceService`
  - Generates job card, invoice, and payment numbers.

## End-To-End Workflow

1. Create Job Card
   - Creates `vehicle_service_job_cards`.
   - Optionally creates inventory lines, labor items, non-inventory items, and labor assignments.
   - Generates `job_card_number`.
   - Recalculates totals.
   - Dispatches `JobCardCreated`.

2. Start Job
   - Status moves to `in_progress`.
   - Listener updates `vehicles.status = in_service`.

3. Allocate Employees
   - Create labor assignments with employee, role, hours, rate, and incentive settings.
   - Multiple employees may be assigned to one job or labor item.

4. Execute Service
   - Add inventory parts, labor items, diagnostics, inspections, and sundries.
   - Recalculate totals before completion/invoicing.

5. Complete Job
   - Status moves to `completed`.
   - Dispatches `JobCardCompleted`.
   - Listener consumes inventory through Inventory service.
   - Listener updates vehicle service dates/odometer and sets vehicle back to `active`.

6. Generate Invoice
   - Creates outbound `invoices` row with `invoice_type = vehicle_service`.
   - Links job card through `invoice_references.document_type = JOB_CARD`.
   - Posts service invoice journal entry.
   - Dispatches `ServiceInvoicePosted`.
   - Listener marks job card `invoiced`.

7. Receive Payment
   - Creates inbound payment.
   - Allocates payment to service invoice.
   - Updates invoice and job card paid amount/balance.
   - Posts payment journal entry.

## Calculation Engine

The calculation engine computes:

- inventory part subtotal, discount, tax
- labor subtotal, discount, tax
- non-inventory subtotal, discount, tax
- header discount
- header tax
- grand total
- balance
- labor item incentive
- labor assignment incentive

Discounts support:

- `percentage`
- `fixed`

Tax/VAT is calculated from active `tax_rates` under a `tax_group_id`, including compound tax support.

## Incentive Engine

Incentives are stored on:

- `vehicle_service_labor_items`
- `vehicle_service_labor_assignments`

Supported incentive types:

- `percentage`
- `fixed`

Labor item incentive base is the labor line net amount. Labor assignment incentive base is `hours_worked * hourly_rate`.

Monthly aggregation and performance reporting can be built from:

- `vehicle_service_labor_assignments.employee_id`
- `hours_worked`
- `incentive_amount`
- `created_at`
- `job_card_id`

## Event Flow

| Event | Listener | Responsibility |
| --- | --- | --- |
| `JobCardCreated` | `UpdateVehicleStatusToInService` | Set vehicle status to `in_service` |
| `JobCardCompleted` | `ConsumeInventoryForJobCard` | Issue stock for consumed parts |
| `JobCardCompleted` | `UpdateVehicleStatusToActive` | Set vehicle status to `active` and update service history |
| `ServiceInvoicePosted` | `RecordServiceInvoicePosted` | Mark job card as `invoiced` |

Critical listeners are synchronous so failures roll back the originating transaction.

## Accounting Journal Mappings

Service invoice:

- Dr Accounts Receivable
- Cr Service Revenue

Inventory consumption:

- Dr COGS or Service Expense
- Cr Inventory

Payment:

- Dr Cash or Bank
- Cr Accounts Receivable

Incentive accrual:

- Dr Labor/Incentive Expense
- Cr Incentive Payable

The current shared journal service posts balanced entries with configured/fallback accounts. Production configuration should prefer item, service, employee, tax, and payment-method account mappings.

## API Surface

VehicleService routes are under `api/vehicle-service`:

- `vehicle-service-job-cards`
- `vehicle-service-job-cards/{id}/start`
- `vehicle-service-job-cards/{id}/complete`
- `vehicle-service-job-cards/{id}/invoice`
- `vehicle-service-payments`
- `vehicle-service-job-card-lines`
- `vehicle-service-labor-items`
- `vehicle-service-non-inventory-items`
- `vehicle-service-labor-assignments`
- `vehicle-service-diagnostics`
- `vehicle-service-inspections`
- `vehicle-service-types`

## Business Rules

- Job card creation must have tenant context and generated job number.
- Job can start from `open`.
- Job can complete from `open`, `in_progress`, or `waiting_parts`.
- Inventory consumption is idempotent per service job-card line.
- Negative stock is blocked by Inventory service.
- Only completed jobs can be invoiced.
- Payments allocate to invoices and update both invoice and job-card balances.
- All lifecycle operations run inside `DB::transaction`.
- Critical records increment `row_version`.

## Production Readiness Notes

- Keep controllers thin: controllers call the orchestrator or use cases only.
- Keep business rules in domain/application services.
- Use shared Inventory, Finance, Payment, Sequence, Employee, and Vehicle modules for integration.
- Add queued listeners later for non-critical reporting, notifications, and external integrations.
- Keep critical stock and accounting listeners synchronous unless an outbox pattern is introduced.
