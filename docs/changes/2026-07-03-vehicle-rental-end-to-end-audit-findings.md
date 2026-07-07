# Vehicle Rental End-to-End Audit Findings

## Scope

Reviewed the current Vehicle Rental backend, frontend, Reporting integration, recent change records, and the empty Laravel log to identify end-to-end mistakes before applying fixes.

## Findings

1. Reservation and expense transitions are not end-to-end version-safe.
   - `RentalTransitionRequest` requires `expected_version`, but `vehicleRentalApi.ts` sends only `status` and `reason` for reservation and expense transitions.
   - `RentalReservationResource`, `RentalExpenseResource`, and their TypeScript interfaces do not expose `row_version`, so the UI cannot build a valid transition payload.
   - The reservation and expense controllers do not pass `expected_version` into their services.
   - The reservation and expense services lock rows, but do not compare the caller's expected version. Their status transitions also do not increment `row_version`.

2. Deposit manage actions cannot satisfy the backend request contracts.
   - Receive/apply/refund/forfeit requests require version fields, payment linkage fields, allocation/refund dates, and refund reason depending on the action.
   - `RentalDepositPage` sends only partial payloads, for example no `expected_requirement_version`, no `expected_payment_version`, no `payment_id`, no `allocation_date`, and uses `payment_date` for refund instead of `refund_date`.
   - `RentalDepositRequirementResource` does not expose requirement `row_version`, and deposit links expose raw `payment_id` / `invoice_id` instead of structured related objects.

3. Reservation-to-agreement conversion is wired in the UI but not completed.
   - `RentalReservationDetailPage` links to `/vehicle-rental/agreements/create?reservation_id=...`.
   - `RentalAgreementCreatePage` does not read `reservation_id`, does not prefill the customer/period, and does not send `reservation_id` to the backend.
   - The backend conversion path exists in `RentalAgreementService`, but the current UI does not trigger it, so confirmed reservations remain unconverted.

4. Agreement lookup direction filters use the wrong backend value.
   - `RentalAgreementLookupSelect` accepts `direction` values `inbound` / `outbound`, then sends the value as `agreement_kind`.
   - The backend agreement filter expects `customer_rental` or `owner_supply`.
   - This can make Billing agreement selection and Expense target agreement selection return no valid agreements.

5. The frontend reintroduces a hardcoded rental billing timezone.
   - The recent integrity record says billing timezone ownership moved to `config/vehicle_rental.php`.
   - `RentalAgreementCreatePage` still defaults and submits `billing_timezone: "Asia/Colombo"`, bypassing the backend configuration default.

6. Financial document creation and vehicle-finance commands are not version-aware.
   - Calculation-run invoice creation has no `expected_version` request rule, and `RentalInvoiceIntegrationService` changes `document_status` without incrementing `row_version`.
   - Vehicle finance activation uses `ListRentalRequest`, accepts no expected version, and updates status without incrementing `row_version`.
   - Vehicle finance payable creation accepts no expected installment/agreement version and links `invoice_id` without incrementing installment `row_version`.

7. Employee reimbursement expenses are exposed but incomplete.
   - The Expense UI offers `employee_reimbursement`.
   - The backend requires an `employee_id` for that allocation type.
   - The UI has no employee selector and never sends `allocations.*.employee_id`, so this option is currently a guaranteed failure.

8. Custody deep links and stale-action handling are incomplete.
   - Allocation detail links to `/vehicle-rental/custody?allocation_id=...`.
   - `RentalCustodyPage` does not read `allocation_id`, so the selected allocation context is lost.
   - Custody confirm is a mutating action but accepts no expected version, and custody resources do not expose `row_version`.

9. Agreement terms still use destructive replacement.
   - `RentalAgreementService::updateDraft()` deletes all existing terms and recreates them when `terms` is present.
   - This matches the open closure-matrix item: no stable term command model or term-level history.

10. Reporting has stale catalog definitions even though the registry masks them.
    - `VehicleRentalReportDefinitionService` has the newer physical/commercial running-chart definitions.
    - `ReportDefinitionRegistry` overwrites catalog definitions by key, so the main registry path likely uses the newer definitions.
    - `ReportCatalog` still contains the older `vehicle-rental.running-chart` with `chargeable_distance_km` and driver overtime scope including removed `consumed` status. This is stale/dead code and can mislead direct catalog users or future edits.

11. User-visible text has encoding damage.
    - Several frontend labels and generated finance invoice descriptions contain mojibake sequences such as `aEUR"`-style punctuation instead of normal separators.
    - This affects date ranges, custody direction labels, finance schedule titles, and generated payable line descriptions.

12. The focused source-contract test suite is currently red.
    - `RentalAgreementIntegrityContractTest` expects an exact array-literal string for `expected_version`.
    - `UpdateRentalAgreementRequest` functionally defines the rule via `$rules['expected_version']`, but the brittle source assertion fails.
    - This blocks a clean verification gate and should be corrected with a less fragile assertion.

## Verification Performed

- Read recent `/docs/changes` records and the core integrity closure matrix before auditing.
- Checked `storage/logs/laravel.log`; it is currently empty.
- Ran `php artisan route:list --path=vehicle-rental`; 55 Vehicle Rental routes are registered.
- Ran `php artisan test tests\Unit\VehicleRental tests\Unit\Reporting\VehicleRentalReportDefinitionServiceTest.php`; 11 passed and 1 failed due the brittle agreement contract assertion above.
- Ran `npx vitest run resources/js/modules/vehicle-rental/vehicleRentalPermissions.test.ts --reporter=dot`; passed.
- Ran `git diff --check`; passed before this audit document was added.

## Suggested Fix Order

1. Fix version-contract payload/resource gaps first: reservation, expense, deposit, finance, and calculation document creation.
2. Fix broken user workflows: reservation conversion, agreement lookup filtering, custody deep link, employee reimbursement.
3. Remove frontend timezone hardcoding and rely on backend/configured defaults or explicit controlled metadata.
4. Clean stale Reporting catalog definitions after confirming registry ownership.
5. Replace destructive agreement-term updates only when the term-edit command requirements are clear.
6. Repair the brittle Vehicle Rental source-contract test and add focused coverage for the broken frontend/backend contracts above.
