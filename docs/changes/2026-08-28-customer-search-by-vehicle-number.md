# Customer search by vehicle number

Date: 2026-08-28

## Purpose

Allow users to find a customer from the Customer List by entering a currently owned vehicle's registration number or internal vehicle number in the existing Search field.

## Changes

- Extended the existing Customer List Search to match current customer-owned vehicle registration numbers and internal vehicle numbers.
- Combined vehicle matches with the existing customer number, code, name, email, phone, and mobile search using OR semantics.
- Kept vehicle matching inside the Vehicle module and passed only matching customer identifiers to the Customer query.
- Applied tenant and organization-unit visibility rules and excluded ended ownerships and soft-deleted vehicles.
- Updated the Search placeholder to mention vehicle-number support.

## Verification

- Focused Customer List API test passed for registration-number search, internal vehicle-number search, and ended-ownership exclusion: 1 test, 12 assertions.
- Customer domain regression suite passed: 5 tests, 24 assertions.
- PHP syntax and Pint formatting checks passed.
- Frontend TypeScript checking passed.
- Production Vite build passed.
- Git whitespace validation passed.
