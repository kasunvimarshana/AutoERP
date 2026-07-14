# Access policy and workspace hardening

Date: 2026-07-14

## Scope

This change closes the confirmed end-to-end access gaps found after the HR navigation review. The work is limited to backend authorization, frontend route entitlements, navigation registration, permission-aware actions, focused tests, and the missing HR/UOM workspace surfaces.

No database schema, Eloquent relationship, domain relationship, migration, accounting rule, or transaction lifecycle was changed.

## Backend authorization

### Voucher source access

Voucher list, type, detail, and print access is now resolved from the source modules the current user can actually view:

- Payment vouchers require the Payment tenant feature and `payments.view`.
- Finance vouchers require the Finance tenant feature and `finance.journals.view`.
- Users with one source permission receive only that source in list filters and type definitions.
- Unauthorized source filters, detail requests, and print requests fail closed.
- Voucher source modules and source kinds use enums instead of embedded validation literals.
- Voucher authorization depends on the core permission and tenant-entitlement contracts rather than concrete module services.

The backend remains authoritative. Frontend hiding does not replace these checks.

## Frontend access ownership

- Removed UOM, Voucher, and Inventory wildcard policies from the Administration registry.
- Registered feature-owned UOM and Voucher route policies.
- Inventory navigation now requires an actual Inventory permission instead of module enablement alone.
- Added UOM navigation and exact list/create/edit/conversion permissions.
- Added HR master-data navigation and UI for departments, designations, employment types, skills, certifications, and licenses.
- HR relation write controls now require `hr.employees.update`; view-only users retain read access.
- Vehicle, Vehicle Service, Vehicle Rental, Finance, Payment, and Purchase actions now follow the same permission semantics as their backend routes.
- Payment and Purchase permission helpers now use the shared access subject, preserving the common super-admin policy.
- Customer, Supplier, Employee, and Payment shortcut actions are filtered through the target route entitlement, enabled module list, organization context, and permissions.
- Query-specific navigation links preserve their own permission constraints while sharing a route pathname.

## UI and data integrity

- HR relationship inputs continue to use controlled lookup components and human-readable related objects.
- Department parent selection uses a controlled selector; raw foreign-key entry was not introduced.
- Existing backend validations, atomic writes, row-version checks, and concurrency behavior remain unchanged.

## Relationship review

The audit reviewed the affected employee/master-data, voucher-source, tenant-feature, and navigation relationships. Existing domain relationships are valid and owned by their current modules. No redundant, circular, bidirectional, or misplaced database relationship required modification for this task.

## Tests added or extended

- Voucher source authorization unit coverage.
- Tenant access integration regressions for Inventory, UOM, HR master data, and Vouchers.
- HR navigation and route-policy coverage for employee and master-data permissions.

## Verification commands

Run from the authoritative `worktree-0.0.8` branch:

- `git diff --check`
- `php artisan test --filter=VoucherAccessPolicyTest`
- `php artisan test --filter=HrPermissionBoundaryTest`
- `php artisan test`
- `composer test:mysql`
- `npm run typecheck -- --pretty false`
- `npm run lint`
- `npm run test`
- `npm run build`

A tenant browser session opened before a plan change still needs a profile refresh, page reload, or new login to receive a changed `enabled_modules` payload. Cross-browser real-time plan propagation requires an explicit push or session-invalidation design and was not simulated with hidden polling in this focused access fix.
