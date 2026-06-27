# Tenant module, entitlement, and permission catalogue correction

## Context

Tenant plan modules, routed feature guards, frontend module metadata, and tenant permissions had diverged. Several routed modules were absent from plan validation, module seeders wrote directly to the User-owned `permissions` table, and sensitive modules lacked an authoritative backend permission boundary.

## Decision

- Tenant owns one canonical module catalogue.
- Foundation modules cannot be toggled by commercial plans.
- Plan-controlled modules are validated against the same catalogue used by runtime entitlement checks.
- User owns the only tenant permission-catalogue writer.
- Modules declare permission definitions through the shared registry but never persist permission rows.
- Tenant-facing sensitive routes enforce registered permissions through User-owned middleware.

## Foundation modules

`auth`, `tenant`, `user`, `organization-unit`, `configuration`, `reference-data`, `audit`, `sequence`, and `uom`.

## Plan-controlled modules

`customer`, `supplier`, `item`, `warehouse`, `inventory`, `purchase`, `sales`, `vehicle`, `vehicle-service`, `vehicle-rental`, `invoice`, `payment`, `finance`, `reporting`, `tax`, `hr`, and `voucher`.

## Integrity notes

- Repeated permission synchronization does not increment row versions unless catalogue metadata changes.
- Stale permission definitions are deactivated rather than deleted.
- Super Admin permission assignments are synchronized from the exact active catalogue.
- Unknown module codes fail fast instead of being silently enabled.
- Frontend route access metadata now uses the same canonical module codes exposed by tenant authentication.
