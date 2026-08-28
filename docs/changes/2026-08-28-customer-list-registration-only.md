# Customer list vehicle registration only

Date: 2026-08-28

## Purpose

Keep the Customer List Current Vehicles column compact by displaying only vehicle registration numbers.

## Changes

- Removed vehicle make and model text from the Current Vehicles column.
- Removed the vehicle-number fallback; vehicles without a registration number do not display a misleading alternate identifier.
- Retained registration-number links to the corresponding vehicle details.
- Removed the now-unused make, model, and vehicle-number fields and joins from the customer vehicle batch response.

## Verification

- Focused Customer List current-vehicle API test passed: 1 test, 4 assertions.
- PHP Pint formatting passed.
- Frontend TypeScript checking passed.
- Production Vite build passed.
- Git whitespace validation passed.
