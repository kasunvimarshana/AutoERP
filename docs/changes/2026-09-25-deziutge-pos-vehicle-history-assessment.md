# Deziutge POS vehicle-history migration assessment

Date: 2026-09-25

## Request and scope

Assessed `C:\Users\Sadheera\Downloads\deziutge_pos (16).sql` as untrusted source data for a proposed vehicle-history migration. The dump was parsed as text only; no source SQL was executed. No application code, migration, or database data was changed.

## Source evidence

- Source database: `deziutge_pos`; file SHA-256: `D06065D44432F08EAC3CE287AE4F936374CF7473AB3AD0657A27F3894DA35DA2`.
- Parsed counts: 2,206 vehicles; 1,635 customers; 4,604 sales; 12,967 product-sale lines; 4,443 payments; 884 products; 6,057 activity logs.
- Sales contain 1,155 `service`, 3,051 `wash`, and 398 rows with no job type. The standalone `jobs`, `job_items`, and `job_item_assignments` tables contain no rows.
- The source schema does not declare vehicle, sale, product-sale, or payment foreign keys. Their data references were checked and no orphan references were found in those relationships.
- Quality findings include 19 duplicate normalized vehicle-number groups overall, 157 vehicles without a customer link, 2 service sales and 135 wash sales without a vehicle, 179 vehicle-linked sales with no job type, 8 service sales whose billed customer differs from the vehicle's stored customer, and uncertain zero/missing mileage values.

## Target-system evidence

- Vehicle identity and ownership are owned by `Vehicle`; service encounters, lines, inspections, and status history are owned by `VehicleService`; financial records belong to `Invoice`, `Payment`, and `Finance`; the combined history view is owned by `Reporting`.
- Immutable Vehicle Service legacy import batch/header/item tables and a dry-run/apply command already exist for a different legacy AutoERP schema.
- The current importer allowlist expects `vehicle_service_jobs` and `vehicle_service_job_lines`, while this source stores service data in `sales` and `product_sales`. The existing command would not recognize the source events, so it must not be used unchanged.
- The latest prior change record documents a separate source import of 18 legacy histories and 59 lines. Cross-source duplicate candidates must be reviewed because source keys differ.

## Proposal status

The remaining report and approval questions were delivered in the task response. No import was performed. Implementation requires approval of target tenant/organization scope, treatment of wash and untyped vehicle sales, and whether immutable invoice/payment snapshots should be visible in vehicle history.
