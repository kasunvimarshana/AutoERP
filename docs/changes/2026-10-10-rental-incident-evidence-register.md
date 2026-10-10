# Rental incident evidence register — 2026-10-10

## Source

Authoritative branch at start: `worktree-0.0.8` commit `6ab137240f177a271337c015e14813a10f1bfb5a`. Verified against `docs/knowledgebase.md`, `docs/vehicle-rental/TODO.md`, TACGL evidence/summary and the source code of fresh Vehicle Rental, Invoice, Payment, Purchase and Finance modules. No legacy Rental runtime was copied or restored.

## Root requirement and ownership

TACGL demonstrates fuel/repair owner deductions and customer debit/credit note terminology. Evidence of a fuel receipt or accident is **not** evidence that a specific customer or owner is liable. An independent Rental-owned evidence record is a prerequisite for any later governed incident recovery. No automatic amount, tax rate, debit note or vendor payment may be inferred solely from a category.

## Implementation (non-monetary slice)

- Introduce typed incident categories, factual status and review actions, and dedicated Incident permissions.
- Create `vehicle_rental_incidents` referencing a valid tenant/org Vehicle Use and optional same-use Running Chart, with tenant-safe composite foreign keys and server-issued reference.
- Create append-only `vehicle_rental_incident_events` capturing actor, action, reason and immutable source snapshot. Model rejects deletion and changes to recorded evidence.
- Add record/view/list/history and version-checked confirm/reject endpoints under `/api/v1/vehicle-rental/incidents`, enforcing tenant/org and dedicated permissions server-side.
- Provide a fast operator-facing incident register using controlled searchable Vehicle Use selection and human-readable labels; expose route entitlements and navigation.
- Record and confirm factual evidence **without writing Invoice, Payment, Purchase, Tax or Finance state**. Confirmation is not a liability decision or debt approval.
- Add focused backend tests for full record/review cycle, immutability, no invoice side effect, chart membership, org boundary, and optimistic version conflict.

## Relationship decision

Incident -> Vehicle Use is the sole canonical assignment link; the existing use resolves customer/owner agreement, vehicle and historical commercial context. Incident -> Running Chart is optional and must belong to the same use. No inverse pointers, duplicate company/customer/owner balance tables, or direct Finance relationships were added. Actor links and event history are one-way tenant-scoped.

## Verification and release gates

The new source and branch diff were inspected using GitHub. PHP and TypeScript runtime tests, full Composer/Vitest suites, InnoDB concurrency, clean schema migration, real tenant role provisioning and browser UAT **cannot be claimed as executed** in this connector-only environment. No production deployment is authorized by this change. Incident financial liability approval/allocations, document-attachment storage, Invoice debit/credit notes, tax treatment, FX, GL posting and settlement remain separate implementation gates in `docs/vehicle-rental/TODO.md`.

Protected TACGL backup entries remain password-protected. The accessible archive contains no independently verified backup password; no password guessing, brute force or credential extraction was performed.
