# Finance Test Fixture Alignment Follow-up

**Date:** 2026-07-13
**Branch:** `worktree-0.0.8`

## Context

The local MySQL migration and targeted Laravel verification confirmed the schema and architecture corrections. The remaining failures were isolated to test-owned setup that created tenant or organization scopes without the posting profiles required by the workflow, direct posted-invoice fixtures without posting plans, one removed journal service reference, and Fast Purchase journal-count expectations that predated Invoice Finance posting.

## Changes

- Seed canonical supplier Invoice, GRNI, payment, and advance profiles in the Purchase engine test context.
- Seed organization-scoped Purchase posting profiles in the Purchase API test context.
- Seed the Vehicle Service Invoice profile in the Vehicle Service engine test context.
- Update Fast Purchase journal expectations to count GRN, Invoice, and Payment postings separately.
- Keep production posting resolution fail-closed when configuration is missing or ambiguous.

## Verification status

The patch was generated from the authoritative `worktree-0.0.8` source context and structurally validated. PHP/MySQL suites must be rerun after applying it.
