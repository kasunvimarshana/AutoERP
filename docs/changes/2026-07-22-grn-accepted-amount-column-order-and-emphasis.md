# GRN accepted amount column order and emphasis

Date: 2026-07-22

## Problem

The new `Accepted amount` column on the Goods Receipt receivable lines table was visible, but it appeared before the later quantity columns and did not stand out enough for quick scanning.

## Change

- moved the `Accepted amount` column to the far right of the receivable lines table, immediately before `Actions`;
- made the accepted amount value bold so the computed line amount reads more clearly in the table.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend presentation of the Goods Receipt receivable lines table.
