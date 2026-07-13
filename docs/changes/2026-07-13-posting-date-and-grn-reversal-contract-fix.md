# Posting Date and GRN Reversal Contract Fix

**Date:** 2026-07-13  
**Branch:** `worktree-0.0.8`

## Context

A local verification run exposed three independent integration defects:

1. Journal posting passed the database storage representation of a cast date into the strict accounting-period validator. Some database drivers return that raw value as `YYYY-MM-DD 00:00:00`, while the Finance period contract correctly requires `YYYY-MM-DD`.
2. The goods-receipt list action called the governed reversal API with only `expected_version`, omitting the required reversal date and business reason.
3. The generic root response test required compiled Vite artifacts even though it only intended to verify the Laravel route response.

## Changes

- `JournalPostingService` now passes the persisted Eloquent date value through `toDateString()` before accounting-period validation.
- `GoodsReceiptListPage` now uses the shared `ReversalDialog` and submits `expected_version`, `reversal_date`, and `reason` to the GRN reversal API.
- `ExampleTest` disables Vite integration for the isolated root-response assertion.

## Boundaries preserved

- Accounting-period validation remains strict and continues to reject invalid, missing, gap, or closed-period dates.
- The Finance validator was not relaxed to accept datetime strings.
- GRN reversal still requires explicit user-supplied business facts; no default reason or hidden date fallback was introduced.
- Production Vite behavior is unchanged; only the isolated backend response test is decoupled from frontend build artifacts.

## Verification status

The changes were reviewed against the supplied Laravel and TypeScript failures and re-read from the authoritative branch after each commit. This environment did not execute PHP, MySQL, TypeScript, ESLint, Vite, or Vitest. The targeted and full verification commands must be rerun locally.
