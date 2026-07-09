# Lessor and Lessee Agreement Data Separation Deep Audit

Date: 2026-07-09

## Scope

Audited the current lessor (`owner_supply`) and lessee (`customer_rental`) agreement flow from routes and React pages through request validation, agreement services, schema, allocations, running-chart usage, commercial usage facts, calculations, invoices/payables, and deposits.

No runtime code was changed in this pass.

## Conclusion

Lessor and lessee records are not the same records and are not supposed to share commercial values. They are two kinds of the same rental-agreement aggregate:

- A lessee agreement belongs to one customer and owns revenue rates, revenue calculations, outbound customer invoices, and an optional security deposit.
- A lessor agreement belongs to one supplier/vehicle owner and owns cost rates, cost calculations, and inbound supplier payables.

The common table and common operational field structure are appropriate. The current UI nevertheless makes the two agreement types look almost identical because it reuses one generic form and detail view and does not expose the legal agreement features already partially represented by the backend.

One physical running-chart record appearing on both sides is intentional. When a customer allocation uses an owner-supply allocation, the system records the trip once and creates separate revenue and cost contexts and separate editable/approvable commercial facts. Those commercial facts initially copy the physical values, so they look identical until a side-specific variance is recorded.

## Findings

### 1. The current screens represent billing configurations, not complete legal agreements

Severity: High

The backend accepts agreement terms, `executed_at`, and `legal_context`, but the create page does not provide terms or execution inputs, the detail page does not render terms, and there is no printable/generated agreement document flow.

An agreement can be activated with only an active rate version. It does not require terms, execution confirmation, or another legal-completeness check.

Impact: a lessor agreement and lessee agreement differ mainly by party and financial direction in the visible workflow. Users cannot capture or review the distinct obligations that normally make the two legal documents different.

Recommended direction: define the actual legal agreement requirements first, then add side-specific term templates or controlled clauses, execution state, and a printable immutable agreement snapshot. Do not create separate duplicate agreement tables; keep the shared aggregate and put distinct legal content in the agreement-owned terms/document boundary.

### 2. The shared create form does not communicate side-specific commercial meaning

Severity: Medium

Both modes render the same agreement fields and the same generic "Core rates" and "Event and recovery rates" panels. The values are persisted under separate agreement/rate-version records, but the UI does not clearly label them as customer charge rates versus owner payable rates.

Impact: users can reasonably conclude that the same data is being captured twice or copied between agreements, even though the backend stores independent values.

Recommended direction: retain the shared component but provide mode-specific labels and guidance. Examples are "Lessee billable rates" and "Lessor payable rates"; lessor event fields should not be described generically as recoveries.

### 3. The detail page hides important differentiating agreement data

Severity: Medium

The detail page shows party, dates, rental mode, billing basis, rates, allocations, and running-chart data. It does not show agreement terms, execution date, legal context, payment terms, currency, remarks, or the lessee deposit requirement, although these values are available in the API resource.

Impact: even correctly separated records appear materially identical on screen, and users cannot review the complete agreement state before activation or downstream billing.

Recommended direction: add a concise agreement summary with the financially and legally important fields, and place clauses/history in a separate tab or expandable section to preserve speed and clarity.

### 4. Agreement lookup `direction` terminology is inverted relative to invoice direction

Severity: Medium

`RentalAgreementLookupSelect` maps `direction="inbound"` to `customer_rental` and `direction="outbound"` to `owner_supply`. Elsewhere, customer-rental calculations generate outbound invoices and owner-supply calculations generate inbound payables.

The billing page therefore passes `inbound` for revenue and `outbound` for cost merely to obtain the intended agreement kind.

Impact: the current behavior works, but the naming is misleading and is likely to cause a future side-selection regression.

Recommended direction: replace the lookup `direction` contract with an explicit `agreementKind` or `financialSide` input and derive the agreement kind in one clearly named source of truth.

### 5. List separation is implemented, but backend endpoint coverage is indirect

Severity: Low

The lessor and lessee list routes send fixed agreement-kind filters, `ListRentalRequest` retains the filter, and `RentalAgreementService::paginate()` applies it. Existing frontend tests verify the mocked API call, but there is no focused HTTP feature test proving that the real agreement endpoint never returns the opposite kind.

Impact: no current list leak was found, but the end-to-end boundary is less strongly protected than the create and calculation paths.

Recommended direction: add one endpoint feature test that creates both kinds and asserts each `agreement_kind` query returns only its own records.

## Confirmed Correct Separation

- Lessee creation requires only a customer; lessor creation requires only a supplier/vehicle owner.
- Security deposits are prohibited for lessor agreements.
- Owner-supplied lessee allocations must reference a version-checked lessor source allocation for the same vehicle and covering period.
- Running-chart data uses one physical record with separate revenue and cost contexts and commercial facts.
- Revenue calculations reject lessor agreements; cost calculations reject lessee agreements.
- Lessee calculations create outbound customer invoices; lessor calculations create inbound supplier payables.
- Rate versions and calculation data belong to their individual agreement IDs; no code path copies a lessee rate version into a lessor agreement or vice versa.

## Runtime Evidence and Verification

- The local database currently contains no `rental_agreements`, so no live duplicate-record case could be inspected.
- All Vehicle Rental migrations are applied.
- `php artisan test tests/Feature/VehicleRental/RentalAgreementCreateTest.php tests/Feature/VehicleRental/RentalCalculationEndToEndFixTest.php tests/Unit/VehicleRental/RentalAgreementIntegrityContractTest.php tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php`
  - 24 tests passed with 401 assertions.
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx resources/js/modules/vehicle-rental/pages/RentalRunningChartPage.test.tsx --reporter=dot`
  - 7 agreement-page tests passed.
