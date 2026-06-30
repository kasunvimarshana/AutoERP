# Vehicle Rental running-chart foundation

## Problem

Vehicle Rental had separate customer and vehicle-owner agreements and rate contexts, but both financial sides consumed the same commercial distance, time, overtime, night-out, and event measurements from the physical running-chart row. The frontend also selected a raw HR employee while the backend required a valid rental driver assignment. Operational usage was marked `consumed` when a financial calculation was approved, mixing operational and financial lifecycles.

## Correct foundation

A running chart now has one physical operational source and independently governed commercial facts:

```text
Physical rental usage
├── Customer / lessee billable fact
└── Vehicle owner / lessor payable fact
```

The physical row owns actual vehicle, driver assignment, timestamps, odometers, route, garage/internal distance, and operational events. Each financial-side context owns one versioned commercial fact with its billable/payable time, odometers, distance, overtime, night-outs, reference, variance reason, lifecycle, and audit actors.

## Main changes

- Added `rental_usage_facts` with tenant-safe constraints, optimistic versioning, independent lifecycle, and retained history.
- Added event applicability: customer, owner, both, or internal.
- Removed client-owned `working_minutes` and raw `driver_id` from usage creation.
- Required allocation-owned driver assignments and blocked overlapping driver usage.
- Added deterministic vehicle and driver timeline locking.
- Added expected-version checks to physical and commercial lifecycle commands.
- Required a variance reason whenever commercial facts differ from physical usage.
- Serialized usage-log, context, and commercial-fact mutations so calculation and reversal cannot race.
- Blocked commercial or physical reversal while any non-reversed calculation exists.
- Calculations now consume only the approved commercial fact for the requested financial side.
- Financial calculations no longer mutate physical usage into a `consumed` operational state.
- Replaced destructive usage/context/event cascades with retention-safe restrictions.
- Rebuilt the Running Chart UI around guided physical entry, valid driver assignments, side-aware events, and separate customer/owner fact panels.
- Removed the obsolete raw employee driver lookup component.

## Verification performed

- Reviewed the complete PR diff against the latest `worktree` baseline.
- PHP lint passed for local copies of the refactored usage and commercial-fact services.
- Checked new MySQL constraint names against the 64-character identifier limit.
- Added focused unit coverage for event applicability and lifecycle separation.
- Confirmed the branch is isolated from `worktree`; GitHub Actions and force-push were not used.

## Open release gates

This batch must remain unmerged until all changed PHP and TypeScript files, Reporting rental definitions, migrations, Laravel routes, MySQL migration/seeding, PHPUnit, TypeScript, ESLint, Vitest, and the production frontend build pass in a runnable checkout. The current execution environment cannot clone or download the repository archive, so those runtime gates are not claimed as passed.
