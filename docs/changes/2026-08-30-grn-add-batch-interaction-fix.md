# GRN Add batch interaction fix

Date: 2026-08-30

## Problem

Clicking `Add batch` in the GRN line editor did not render an allocation row even after received and accepted quantities were entered.

## Root cause

The generic line-field updater first assigned the new `batch_allocations` value and then immediately overwrote it with the previous allocation array. The existing frontend test asserted only that the button was visible, so it did not exercise the broken interaction.

## Changes

- Preserved explicit `batch_allocations` updates while retaining accepted-quantity synchronization for a single allocation.
- Extended the GRN regression test to click `Add batch`, verify the allocation fields render, verify the accepted quantity is copied into the first allocation, and verify entering a batch number enables line saving.
- Allowed Inventory batch lookup searches to match either batch number or supplier lot number.
- Included the supplier lot number in human-readable batch lookup labels.
- Kept the current non-variant flow unchanged; no speculative variant UI was added.

## Verification

- Purchase and Inventory frontend suites passed: 13 tests.
- Inventory batch backend tests passed: 2 tests, 6 assertions.
- TypeScript typecheck passed.
- Focused ESLint checks passed.
- Production frontend build passed.
- Laravel Pint and PHP syntax checks passed.
- `git diff --check` passed.
