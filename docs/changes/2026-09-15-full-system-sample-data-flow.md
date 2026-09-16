# Full-system sample data flow

Date: 2026-09-15

## Summary

Added a connected manual acceptance-test dataset and expected-result guide covering the current AutoERP tenant workspace from master-data setup through Purchase, Inventory, Vehicle Service, Invoice, Payment, Finance, Reporting, Vouchers, and Audit.

## Coverage

- Includes platform/tenant/access, organization context, configuration, reference data, UOM, warehouse, item, supplier, customer, vehicle, and HR setup.
- Reconciles one main Purchase-to-stock-to-service-to-cash story with explicit quantities and monetary expected results.
- Includes independent returns, adjustments, opening stock, reservations, cheque, manual invoice/journal, budget, reconciliation, tax, reversal, security, tenant-isolation, idempotency, and concurrency cases.
- Covers Vehicle Rental conditionally because its routes remain implemented while tenant sidebar navigation is currently hidden.
- Distinguishes configured test tax data from statutory/legal advice.

## Why

The system needed one repeatable manual data flow that tells testers both what to enter and what output/status/cross-module effect to expect, rather than isolated feature checks that cannot be financially or operationally reconciled.

## Verification

- Checked the arithmetic and final reconciliation table for the connected scenario.
- Documentation-only change; no application runtime behavior was modified.
