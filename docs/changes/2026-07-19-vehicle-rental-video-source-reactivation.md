# Vehicle Rental video-source reactivation

**Date:** 2026-07-19

## Decision

The product owner explicitly reactivated Vehicle Rental and confirmed that all reviewed Vehicle Rental videos are the business source of truth. The user-facing workflow must remain no more complex than the demonstrated legacy workflow, while backend integrity must prevent the legacy system's unsafe states.

## Change

- restored the last complete pre-decommission Vehicle Rental implementation baseline, including backend, fresh-schema migrations, provider, API, permissions, frontend, reports, Finance provisioning, Invoice/Payment handoffs, and validation tests;
- restored the post-simplification design that keeps Lessee and Lessor agreements separate, uses one physical Running Chart as operational evidence, and derives customer billing and owner settlement independently;
- restored contextual vehicle assignment through agreements instead of introducing another mandatory user-facing allocation wizard;
- retained effective-dated allocations, agreement/rate snapshots, source-consumption identity, optimistic versions, deterministic locks, immutable posting boundaries, reversal paths, and audit history;
- preserved all verified later Item and Vehicle Service work without carrying forward the Vehicle Rental decommission runtime changes;
- retained the previous removal record as append-only historical evidence rather than deleting or rewriting shared history.

## User-facing workflow

```text
Vehicle / Customer / Owner Setup
→ Owner Agreement
→ Customer Agreement
→ Select Vehicle
→ Daily Running Chart
→ Customer Invoice
→ Owner Payable
→ Receipt / Payment
→ Reports
```

Reservations, handover/return, replacement, deposits, adjustments, and reconciliation remain supporting capabilities. They must not become mandatory extra steps unless the business explicitly requires them.

## Relationship review

The restored relationships have valid business ownership and are retained:

- Lessor agreement → effective vehicle supply allocation;
- Lessee agreement → effective customer vehicle allocation;
- one physical Running Chart → independent customer and owner commercial contexts;
- calculation sources → immutable Invoice documents;
- deposit movements → Payment-owned allocations;
- vehicle availability → Vehicle-owned status and Vehicle Service operational blocking.

No relationship was removed or rewired blindly. A future move of Vehicle Finance requires a separately approved owning module and an explicit data migration; it is not hidden inside this reactivation through a compatibility alias or partial relocation.

## Unconfirmed policies

No new default was introduced for partial-month proration, replacement charging, downtime deductions, free-kilometre pooling, garage-mileage billing, accident/insurance excess, early termination, or deposit application priority. Those policies require business confirmation before implementation.

## Verification boundary

The resulting tree is based on the previously integrated and tested complete Vehicle Rental baseline, with later verified non-rental changes overlaid by exact blob identity. Connector access does not provide a runnable checkout, so the full Laravel SQLite/MySQL suites, TypeScript, ESLint, Vitest, production build, route listing, and `migrate:fresh --seed` must be run on a normal checkout before deployment.
