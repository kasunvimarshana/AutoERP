# Payment refund policy consolidation

Date: 2026-07-16

## Problem

Refund identity rules were duplicated between Payment creation validation and Finance posting policy resolution. Both paths independently checked the original payment, scope, party, direction, and refund-of-refund prohibition. The posting path did not enforce the same currency identity as creation.

Duplicated financial policy can drift and allow a Payment that creates successfully but cannot post consistently.

## Correction

Payment now owns one `PaymentRefundPolicyService` that resolves and validates the original payment for both:

- create-command validation;
- posting-policy resolution.

The canonical policy enforces:

- an original payment is required;
- the original must exist;
- a refund cannot refund another refund;
- tenant, organization, party, and currency identity must match;
- refund direction must reverse the original payment direction.

The existing creation-only requirements for `payment_refund` source identity and no direct invoice allocations remain in `PaymentValidationService` because those are create-command constraints, not original-payment identity rules.

## Ownership and relationships

No schema or model relationship changed.

The original-payment relationship is a valid self-reference owned by Payment. The change removes duplicated policy logic without introducing a second relationship or a compatibility path.

## Verification

Focused coverage verifies that creation and posting resolve the same original Payment and that posting rejects currency drift.

Run:

```bash
git diff --check
php artisan test --filter=PaymentRefundPolicyServiceTest
php artisan test --filter=PaymentCreationIdempotencyTest
php artisan test
composer test:mysql
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
```
