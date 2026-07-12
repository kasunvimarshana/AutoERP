# Rental Billing responsive review test correction

Date: 2026-07-12

## Evidence

The latest Windows frontend verification passed TypeScript, ESLint, and the Vite production build, but one Vitest remained red. The Rental Billing review renders mobile and desktop line presentations in the DOM at the same time, so global text queries found duplicate calculation descriptions even though the workflow itself behaved correctly.

## Correction

- The behavioral test now locates the opened calculation review panel from its heading.
- Calculation description, usage reference, and chargeable quantity assertions are scoped to that panel.
- The approval action is also selected from the same review panel.
- Responsive mobile/desktop duplicates are accepted as intentional presentation details instead of being treated as separate business records.

## Scope

- Test-only change in the Vehicle Rental frontend owner module.
- No production UI, calculation, invoice, payment, API, schema, or accounting behavior changed.

## Verification required

Run from the latest `worktree-0.0.8` branch:

```bash
npm run test -- RentalBillingReviewWorkflow.test.tsx
npm run test
```
