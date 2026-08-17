# Vehicle Service whole-job discount

Date: 2026-08-14

## Purpose

Allow an authorized user to apply a fixed or percentage discount to an entire Vehicle Service job while retaining the existing per-line discounts and preserving correct invoice allocation.

## Changes

- Added separate line-discount, whole-job-discount-base, and whole-job-discount totals to Vehicle Service jobs; the existing discount total now represents their combined value.
- Added an append-only whole-job discount revision table recording every set, change, and removal with its calculation snapshot, required reason, actor, and timestamp.
- Added a dedicated `vehicle_service.discounts.manage` permission and version-checked API operations for setting and removing discounts.
- Kept discount calculation authoritative in the Vehicle Service backend: percentage discounts recalculate when eligible lines change, fixed discounts cannot exceed the amount remaining after line discounts, and tax calculation remains unchanged.
- Limited discount changes to mutable job states and blocked changes after invoicing starts.
- Added a focused discount editor to the job overview with fixed/percentage input, reason, calculation preview, and separate line/job/combined discount totals.
- Mapped the job discount to an invoice header adjustment. Partial invoices receive a proportional allocation and the final invoice receives the exact remaining amount.
- Updated line-mutation responses so the UI receives authoritative recalculated job totals immediately.

## Verification

- Added backend coverage for combined line and job discounts, percentage recalculation, immutable revision history, removal, and exact allocation across partial invoices.
- Added frontend coverage for setting and removing a whole-job discount.
- Passed the focused Vehicle Service frontend regression tests, TypeScript typecheck, targeted ESLint, PHP syntax checks, Laravel Pint, and the production frontend build.
