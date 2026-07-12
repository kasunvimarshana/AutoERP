# Rental deposit receipt identity hardening

Date: 2026-07-12

## Evidence

Vehicle Rental security deposits are received through a Payment-owned document created as:

- party type `customer`;
- payment type `advance`;
- direction `inbound`;
- source type `rental_deposit_requirement`;
- the exact rental customer, tenant, organization, currency, requirement, and row version.

The later deposit application, refund, and forfeiture workflows reloaded and locked the selected payment, then validated scope, customer ID, currency, source identity, approval/posting state, and the active receipt link. They did not explicitly revalidate the payment party type, payment type, or direction before consuming that receipt.

## Correction

- Added one Vehicle Rental-owned receipt identity boundary used by application, refund, and forfeiture.
- The selected payment must now match the complete deposit receipt contract:
  - current positive row version;
  - tenant and organization;
  - customer party type and customer ID;
  - deposit currency;
  - `advance` payment type;
  - `inbound` direction;
  - deposit requirement source type and source ID.
- Existing approved/posted and active-link checks remain unchanged and execute after identity validation.
- Replaced repeated deposit party/source literals in the same service with descriptive constants.

## Ownership and scope

- Vehicle Rental owns the rule that a payment can be consumed as a rental security-deposit receipt.
- Payment continues to own payment creation, lifecycle, posting, refund, allocation, reversal, and instrument behavior.
- No schema, API payload, deposit amount calculation, invoice balance, finance posting, tax, or frontend behavior changed.
- No compatibility fallback or cross-module workaround was introduced.

## Verification

- Added focused behavioral coverage for the exact accepted identity and rejection of:
  - a non-customer party type;
  - a non-advance payment type;
  - an outbound payment direction.
- Reconstructed the service from the authoritative branch and verified that removing this patch reproduces the previous Git blob SHA exactly, preventing accidental full-file rewrite drift.
- PHP syntax validation passed for the modified service and new test.

Run from the latest `worktree-0.0.8` branch:

```bash
php artisan test --filter=RentalDepositReceiptIdentityTest
php artisan test
composer test:mysql
```
