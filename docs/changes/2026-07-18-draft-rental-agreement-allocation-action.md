# Draft rental agreement allocation action

**Date:** 2026-07-18

## Problem

The allocation service accepts both Draft and Active rental agreements and creates a new allocation in Planned status. The agreement detail UI exposed the allocation action only after agreement activation, so a Draft lessee agreement could not follow the supported plan-before-activate workflow.

## Correction

- Expose the vehicle-allocation action for both Draft and Active agreements through one named status decision.
- Keep the existing agreement-kind wording: `Assign vehicle` for a lessee agreement and `Register supplied vehicle` for a lessor agreement.
- Replace the misleading empty-state instruction with the actual supported workflow: create a Planned allocation for the agreement.
- Add focused frontend regression coverage for a Draft lessee agreement.

## Relationship review

No database, model, API, route, or module relationship changed.

The valid Agreement-to-Allocation relationship remains effective-dated and separate. A Draft agreement may own a Planned allocation, while allocation activation and vehicle handover continue to require an Active agreement. No source-allocation, ownership, vehicle-availability, Invoice, Payment, Tax, or Finance rule changed.

## Verification

```bash
npm run test -- resources/js/modules/vehicle-rental/pages/RentalAgreementDetailAllocationAction.test.tsx
npm run typecheck -- --pretty false
npm run lint
npm run build
git diff --check
git status --short
```
