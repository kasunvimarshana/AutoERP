# Lessee/Lessor Agreement Audit Findings

## Context

Audited the current end-to-end lessee agreement (`customer_rental`) and lessor agreement (`owner_supply`) flows after the agreement workflow, running-chart panel, and allocation fixes.

## Findings

1. Draft agreement edits can invalidate child aggregates.
   - `RentalAgreementService::updateDraft()` allows changing party, agreement period, billing settings, payment terms, currency, and remarks while the agreement is still `draft`.
   - Draft agreements can already have planned allocations and active rate versions.
   - The update path does not revalidate existing allocations against the new agreement period/source-party meaning, and does not revalidate active rate version effective periods or copied billing/currency fields against the updated parent agreement.
   - Owner module: `RentalAgreementService` should own this guard because it owns agreement mutation. It should either block contract-shaping edits once child aggregates exist or revalidate all dependent aggregates atomically before saving.

2. Lessor agreement creation silently accepts unsupported deposit payloads.
   - `StoreRentalAgreementRequest` accepts a `deposit` array for all agreement kinds.
   - `RentalAgreementService::create()` only creates a deposit requirement for `customer_rental`; for `owner_supply` the payload is ignored.
   - This is a backend contract issue because unsupported input should be rejected, not silently discarded. Deposits belong only to lessee/customer rental agreements.

3. Deposit schema does not enforce the customer-rental-only invariant.
   - `RentalDepositService` rejects operations when the linked agreement is not `customer_rental`, but `rental_deposit_requirements` only has a foreign key to `rental_agreements`.
   - If a bad deposit requirement is inserted by a future code path or manual data load, deposit listing/reporting can expose it and link it through the lessee route.
   - The deposit aggregate should enforce or derive the customer-rental-only rule at creation/storage boundaries, not rely only on operation-time checks.

4. Mode-specific agreement detail pages warn on wrong agreement kind but still expose normal actions.
   - `/vehicle-rental/lessee-agreements/:id` and `/vehicle-rental/lessor-agreements/:id` show an alert when the loaded agreement kind does not match the route mode.
   - The page still renders allocation links, activate/terminate actions, and running-chart panels for the loaded record.
   - The backend remains kind-safe, but the UI should block actions or redirect to the correct route so users cannot operate on a lessor agreement from a lessee surface or vice versa.

## Healthy Areas Confirmed

- Agreement list and create pages force the correct kind in lessee/lessor modes.
- Party inputs are controlled lookup components, not raw IDs.
- Billing calculation side is enforced in the backend before billing period creation.
- Billing UI filters agreements by financial side and resets the selected agreement when the side changes.
- Invoice/payable creation maps customer agreements to outbound rental invoices and owner-supply agreements to inbound supplier payables.
- Running-chart detail panels request the correct financial side for the loaded agreement kind.

## Verification

- `php artisan test tests/Feature/VehicleRental/RentalAgreementCreateTest.php tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/RentalCalculationIntegrityContractTest.php`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx resources/js/app/navigation/navigationUtils.test.ts --reporter=dot`
