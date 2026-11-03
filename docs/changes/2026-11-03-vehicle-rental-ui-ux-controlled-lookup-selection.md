# Vehicle Rental UI/UX Controlled Lookup Selection & Agreement Search Enhancement

**Date:** 2026-11-03  
**Module:** Vehicle Rental  
**Branch:** `worktree-0.0.8`

## Context & Purpose
Project design standards explicitly require that all relationship inputs use controlled selection components (e.g., `LookupSelect` with search and autocomplete) displaying human-readable references and party names, rather than exposing raw numeric IDs or requiring users to manually type database keys.

In `RentalRunningChartReportPage.tsx`, report generation previously exposed a raw numeric input for `agreement_id`. This change upgrades the selector to a controlled `LookupSelect` and enhances backend search capabilities across customer agreements.

## Changes Made

### 1. Backend Service Search Enhancement (`AgreementService.php`)
- Updated `AgreementService::list` to support searching both the agreement `reference` and the associated `party` name (`orWhereHas('party', fn ($p) => $p->where('name', 'like', '%'.$search.'%'))`).
- Allows users to search for agreements by agreement code (e.g., `CA-2026-0001`) or customer/supplier name (e.g., `Lanka Industrial Corp`) interchangeably.

### 2. Agreement Frontend API (`agreementApi.ts`)
- Added `getAgreement(kind, id)` to fetch individual agreement records with relations.
- Added `searchCustomerAgreements(params)` using standard `requestLookup` and `mapLookupResult` to return structured `{ id, name: "${ag.reference} — ${ag.party.name}" }` resources for `LookupSelect`.

### 3. Report Parameter Form Modernization (`RentalRunningChartReportPage.tsx`)
- Replaced the raw numeric `Customer Agreement ID` input with `<LookupSelect label="Customer Agreement" search={searchCustomerAgreements} ... />`.
- Supported deep-linking and cross-page navigation by resolving `agreement_id` query parameters into human-readable agreement titles upon initial render.
- Disabled form submission while agreement selection is empty.

### 4. Automated Tests
- Updated `RentalRunningChartReportPage.test.tsx` to verify controlled `LookupSelect` combobox interaction, option selection, and report generation.

## Verification
- Vitest suite passed: `npx vitest run resources/js/modules/vehicle-rental` (25 test files passed, 95 tests).
- Backend suite passed: `php artisan test --filter=VehicleRental` (162 tests passed, 1,457 assertions).
- TypeScript check passed: `npm run typecheck -- --pretty false` (0 errors).
- ESLint passed: `npm run lint` (0 errors).
