# ERP Migration Bundle

This bundle is organized by module:

Core → Identity → Finance → Product → Warehouse → Inventory → Procurement → Sales → Returns → Traceability → Audit → Config

## Design notes
- Tenant-owned tables use `tenant_id`.
- Tenant-scoped business identifiers use composite unique keys.
- Line tables cascade from headers.
- Optional document links use `nullOnDelete()`.
- Polymorphic audit and trace tables are indexed by type/id pairs.
- Numeric precision uses `decimal(18,4)` for money/quantity and `decimal(20,8)` for exchange/rate fields.

## Suggested run order
1. Core
2. Identity
3. Finance
4. Product
5. Warehouse
6. Inventory
7. Procurement
8. Sales
9. Returns
10. Traceability
11. Audit
12. Config

## Notes
- The bundle favors tenant-safe uniqueness over global uniqueness where the domain is tenant-owned.
- The schema keeps JSON only for metadata/raw payload fields.
- Cross-module journal and stock references remain explicit so posting logic can stay deterministic.
