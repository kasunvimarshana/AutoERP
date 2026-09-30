# Customer list current vehicles

Date: 2026-08-28

## Purpose

Simplify the Customer List and show each customer's currently owned vehicles without adding per-row requests or exposing raw relationship identifiers.

## Changes

- Retained the main customer Search input and removed the Customer type, Status, Category, Credit allowed, Sort, and Direction controls from the list UI.
- Removed the Type, Categories, and Credit columns from the Customer List table.
- Added a Current Vehicles column with linked registration numbers, falling back to the vehicle number, plus available make and model names.
- Added a Vehicle-owned batch provider that resolves current customer ownerships for all customers on the current page in one query.
- Used the authoritative current-ownership flag and customer owner type while excluding ended ownerships and soft-deleted vehicles.
- Enforced tenant and organization-unit visibility in the vehicle batch query.
- Kept customer API filtering capabilities and existing summary fields available for lookup and API consumers.
- Preserved current-vehicle data in the list when an inline status action updates a customer row.

## Verification

- Focused Customer List API test passed for current, ended, deleted, and no-vehicle cases.
- Customer and Vehicle regression suites passed: 19 tests, 158 assertions.
- PHP Pint formatting passed for the changed backend and test files.
- Frontend TypeScript checking passed.
- Production Vite build passed.
- Git whitespace validation passed.
