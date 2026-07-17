# Vehicle Rental agreement UI/UX simplification and activation review

**Date:** 2026-07-17

## Scope

Implemented the evidence-backed, policy-safe UI/UX corrections from the Vehicle Rental video audit without changing unconfirmed calculation policies or weakening historical, Tax, concurrency, or allocation controls.

## Changes

### Agreement drafting

- Reorganized the Lessee/Lessor editor into clear Contract, Billing Policy, Pricing, and Review sections.
- Added contextual party labels: `Customer / Lessee` and `Vehicle owner / Lessor`, while retaining the shared Customer and Supplier/Party masters as the relationship source of truth.
- Replaced the always-visible full rate catalogue with a focused rate builder:
  - required/common rates remain visible;
  - driver, overtime, and night-out rates appear only for with-driver contracts unless an existing non-zero contract rate must remain visible;
  - event rates are added through a controlled `Add optional charge` selector;
  - optional rates can be removed explicitly and are then excluded from the payload;
  - currency, unit, side-specific meaning, and Tax treatment remain visible.
- Added a persistent save-readiness checklist rather than leaving the primary action disabled without explanation.
- Added an inline API error summary and preserved field-level errors.
- Clarified that Security Deposit is a requirement only; receipt/refund ownership remains with Deposit/Payment workflows.
- Clarified that Remarks are printed in the activated agreement snapshot.
- Preserved optional agreement-specific clauses and improved their accessible removal labels.
- Preserved all current API payloads, optimistic versions, reservation conversion, structural locking, immutable rate history, and Lessee-only deposit rules.

### Agreement list

- Removed the redundant Kind column from side-specific Lessee and Lessor lists.
- Added human-readable status filters, search guidance, URL-persisted filters/page, whole-row navigation, allocation/vehicle context, and improved mobile/empty states.

### Activation and detail review

- Added an explicit Draft activation-readiness panel.
- Draft agreement details now display the exact pending rate version, effective period, billing basis, proration, included KM, rate units, and Tax treatment before activation.
- Activation is blocked with visible reasons when party, contract period, execution date, legal context, or exactly one Draft rate version is missing.
- The confirmation explains that activation freezes the reviewed financial and printable terms.
- Added contextual `Assign vehicle` / `Register supplied vehicle` actions after activation.
- Clarified null payment terms (`Not specified`) versus explicit zero (`Due immediately`).
- Added allocation period context and a Deposit activity link.

### Printable agreement

- The Rental Agreement API projection enriches the immutable stored document snapshot with Tax treatment from the exact immutable rate version identified by the snapshot version number.
- The print view displays side-specific rate labels and Taxable/Non-taxable treatment.
- No Tax value is inferred or defaulted. If the exact immutable rate projection is unavailable, the print shows `Not captured`.

### Navigation ownership

- Removed duplicate Rental navigation entries for generic Agreements, Customer Invoices, Owner Payables, and Settlements.
- Invoice and Payment records remain owned and accessible through their owning modules and the Rental calculation handoff.
- Renamed global custody access to `Handover & Return Queue` because handover, return, and replacement remain contextual Allocation operations.
- Renamed the calculation workspace to `Billing & Settlement`.

## Relationship review

No database relationship was removed or rewritten.

The following relationships remain justified and were preserved:

- Lessee Agreement and Lessor Agreement remain independent because customer revenue and owner cost use different commercial terms.
- Agreement and Vehicle Allocation remain separate because contract terms are not the physical vehicle assignment timeline.
- One physical Running Chart continues to feed independent revenue and cost contexts.
- Customer Invoice, Owner Payable, and Payment records remain owned by Invoice/Payment modules.

The following were intentionally not changed because the available evidence does not justify a safe migration in this task:

- financed vehicle-source relationships and Vehicle Finance data;
- partial-period and included-KM policy formulas;
- replacement billing/owner-payment policies;
- fuel, repair, downtime, or insurance responsibility policies;
- Driver assignment role versus primary designation semantics.

Removing or rewriting those relationships without stakeholder-approved ownership and migration rules would risk orphaning valid historical data.

## Tests added/updated

- Focused rate-builder visibility and optional-charge tests.
- Draft activation review and blocker tests.
- Printable agreement Tax-treatment test.
- Backend resource regression asserting Tax treatment is projected from the exact immutable rate version.

## Required verification

```bash
php artisan test --filter=RentalAgreementOptionalTermsTest
npm run test -- resources/js/modules/vehicle-rental/components/RentalAgreementRateBuilder.test.tsx resources/js/modules/vehicle-rental/components/RentalAgreementPrintDocument.test.tsx resources/js/modules/vehicle-rental/pages/RentalAgreementActivationReview.test.tsx resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx
npm run typecheck -- --pretty false
npm run lint
npm run build
php artisan test
composer test:mysql
npm run test
git diff --check
git status --short
```
