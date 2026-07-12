# Vehicle Rental running-chart lock-order hardening

Date: 2026-07-12

## Evidence

Running-chart creation previously entered the lower-level usage service after loading one selected allocation. The service then locked the selected allocation, optionally locked its owner-supply allocation, and only later locked the complete vehicle and driver timelines. Concurrent requests starting from different allocations for the same vehicle could therefore acquire shared rows in different orders.

The legacy-video-derived business model requires one authoritative running-chart stream to safely serve independent lessee revenue and lessor cost calculations. Duplicate or conflicting usage must be prevented at write time rather than repaired later.

## Correction

- Added `RentalUsageCreationService` as the authoritative command boundary for recording a running-chart entry.
- The command runs in one outer database transaction and acquires shared resources in a deterministic order:
  1. all allocations for the tenant and vehicle, ordered by ID;
  2. selected and owner-supply allocation identity validation;
  3. all assignments for the selected driver, ordered by ID;
  4. the existing usage validation, context, event, fact, and persistence engine.
- Allocation, source-allocation, and driver identities are re-read after timeline locking and fail closed when they changed while the command was being prepared.
- The HTTP controller now delegates only running-chart creation to this command service. Listing, reading, and lifecycle transitions remain with the existing usage service.

## Scope

No schema, route, request, response, calculation formula, status transition, odometer rule, rate rule, invoice integration, or finance ownership changed. No database-specific advisory lock or retry workaround was introduced.

## Verification

- The original controller was reconstructed and matched its authoritative Git blob before applying the scoped change.
- Modified and new PHP files pass syntax validation.
- A lock-order contract test verifies the command ordering, controller boundary, and fail-closed identity checks.
- The complete SQLite, MySQL/MariaDB, TypeScript, ESLint, production-build, and frontend test gates must be rerun from the resulting branch head before release approval.
