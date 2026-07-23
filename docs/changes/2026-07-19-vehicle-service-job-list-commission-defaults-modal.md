# Vehicle service job list commission defaults modal

Date: 2026-07-19

## Problem

The `Commission defaults` action on the vehicle service job list expanded an inline card under the page header. That pushed the table downward and interrupted the user’s view of the job list.

## Change

- moved the vehicle service job list `Commission defaults` UI from an inline panel to the shared modal component;
- kept the existing commission settings form, save behavior, success toast, and auto-close flow unchanged;
- removed the inline layout shift so the jobs table remains visually stable while editing defaults.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend presentation of the commission defaults action on the vehicle service job list page.
