# Vehicle Service promotions and card flow refinement

Date: 2026-11-02

## Request

Refine the product plan using a supplied physical card example, while keeping current manual discount entry and making Service Card selection and buy-five progress easy to use in a service job.

## Changes

- Updated the existing architecture plan with card setup fields, optional branch/weekday restrictions, customer/vehicle offer assignment, the top-of-job progress/card strip, and one-action insertion of selected benefit items at normal prices.
- Kept manual discount input and workflow unchanged in the plan. Proposed separate promotional discount accounting and a visible combined-price breakdown.
- Clarified that printed prices and promotional claims in the example card are reference data, not defaults.
- Application code, schema, data, and older change records were not modified.

## Verification

- Re-read recent change records and inspected the existing Vehicle Service create/detail screens and line discount calculation.
- Planning/documentation only; no application tests apply.
