# Customer phone or WhatsApp number required

Date: 2026-10-31

## Request

Require each newly created customer to have a phone or WhatsApp number, and prevent an existing customer's last number from being removed.

## Changes

- Enforced the at-least-one-number rule in the Customer creation service, covering regular creation, Service Job quick-create, and other flows that use the same service.
- Customer edits reject an explicit change that would leave both number fields empty. Existing customer records with no number can still be edited in other fields; the Edit page omits untouched empty number fields for those legacy records. Adding a number remains allowed.
- Kept Customer Contact number fields optional; they do not replace the customer's own required number.
- Extended the read-only `customers:audit-phone-numbers` output to identify existing customer records that have neither number, alongside duplicate and invalid values. Existing data is not changed automatically.
- Added regression coverage for required creation, legacy record edits and repair, rejecting removal, and auditing missing records.

## Verification

- Customer, vehicle, customer list, and WhatsApp related backend suites: 29 passed, 253 assertions.
- Service Job quick-create UI regression: 1 passed.
- TypeScript typecheck and targeted ESLint, Pint, and `git diff --check` were run.

The targeted ESLint run reports one existing `react-hooks/set-state-in-effect` warning in `VehicleServiceQuickVehicleModal.tsx`; it reports no errors. No production database migration or data rewrite was required.
