# Vehicle Rental release verification fixes — 2026-10-05

## Scope

Correct the concrete regressions exposed by the first dependency-backed local verification run after the Vehicle Rental financial-foundation release.

Starting authoritative state:

- branch: `worktree-0.0.8`
- head: `ed0c85da38ea83bf64b654b75ae3579ce6ebe5e1`
- release commit: `feat(vehicle-rental): complete financial settlement foundation`
- open pull requests at correction start: none
- correction branch: `agent/vehicle-rental-release-verification-fixes-20261005`

No removed/legacy Rental runtime was restored, reused, copied or referenced.

## Executed pre-fix verification evidence

The supplied local run from `D:\project-new\kasun\AutoERP` executed the real dependency-backed commands against the released source.

Results before this correction:

- `php artisan test`: **884 passed / 4 failed / 9,723 assertions**;
- `npm run typecheck -- --pretty false`: failed with **9 Vehicle Rental type errors**;
- `npm run lint`: failed with **1 error and 1 warning**;
- `npm run build`: **passed**, 697 modules transformed;
- `npm run test`: **405 passed / 8 failed**, across **102 passing / 7 failing test files**.

These results supersede the earlier statement that a dependency-backed run for the financial-foundation delta was unavailable: the earlier statement was true for that execution environment, while this later user-supplied local run provides actual release-head evidence.

## Root causes and corrections

### 1. Finance upgrade migrations crossed tenant boundaries without declaring control-plane execution

The new Finance base-currency backfill/rebuild migrations intentionally process every tenant, but their raw tenant-owned table queries did not declare the platform control-plane boundary required by the tenant architecture contract.

Fix in the Finance-owned upgrade migrations:

- wrap cross-tenant ledger backfill/rebuild work in the established `TenantExecutionContext::runAsControlPlane(...)` boundary;
- keep the existing transaction boundaries;
- do not weaken the tenant-isolation architecture test and do not add a suppression.

No Finance account, journal, ledger or Rental relationship was changed.

### 2. Invoice pure validation acquired database/reference responsibilities

`InvoiceValidationService` is also used as a pure calculation/domain validator. The financial-foundation release added Tenant/Currency model lookups there, causing calculation tests without migrated tenant tables to fail before reaching the calculation rule being tested.

Fix in Invoice ownership:

- keep `InvoiceValidationService` persistence-free for structural/decimal/business-input validation;
- move active-currency and tenant-base-currency exchange-rate validation to `InvoiceCreationService`, the create/preview persistence/reference boundary;
- preserve the base-currency rate-one invariant for real Invoice creation/preview;
- do not fabricate tenant fixtures in pure calculation tests.

### 3. Invoice register PDF orientation assertion was stale

The Invoice Register gained Currency and Exchange Rate columns. The generic report orientation rule correctly selects landscape when the report has more than seven columns.

Fix:

- keep the wider Invoice Register in landscape;
- update the stale Reporting framework assertion rather than forcing a wide financial report back into portrait.

No PDF export mechanism was changed.

### 4. Agreement currency frontend type was weaker than the API contract

The Agreement API resource always returns the immutable currency code snapshot for a valid agreement, but the frontend reused generic `NamedResource`, where `code` is nullable/optional. This produced the reported TypeScript failures in Agreement, Base Rent and Deposit views.

Fix:

- strengthen only the Agreement currency type to `NamedResource & { code: string }`;
- do not add null fallbacks that would hide a violated Agreement API invariant.

### 5. Mileage 422 validation incorrectly discarded the working quote/form

The mileage assessment panel cleared its quote after every failed submission. For ordinary 422 document-field validation, that removed the input carrying the field error, leaving only the global error list.

Fix:

- keep the quote/form for ordinary validation failures so field errors remain attached to their inputs;
- invalidate the quote only for 409 stale/conflict responses where the commercial quote genuinely must be reloaded.

This is the only runtime Vehicle Rental UI behavior correction in this verification pass.

### 6. Frontend lint regression

Fixes:

- remove the unused `Input` import from `RunningChartsPanel`;
- stabilize Payment allocation invoice rows with an explicit memoized dependency, avoiding a changing-array hook dependency.

No Payment allocation rule changed.

### 7. Vehicle Rental Vitest assertions drifted from the accessible UI

Several failures were test-contract drift rather than runtime defects:

- formatted quantities are rendered through `QuantityDisplay`, so label/value/unit can span child nodes;
- Running Chart history renders `Air conditioning: Front AC` as one semantic line;
- confirmation actions use explicit labels such as `Confirm reversal`, `Confirm cancellation` and `Confirm handover`;
- Vehicle Use lookup test setup queued mock responses across tests because `clearAllMocks()` does not reset one-shot mock implementations.

Fixes:

- assert formatted values through their semantic field/container;
- assert the actual action-specific accessible button labels;
- reset and route lookup mocks by endpoint so vehicle and owner-source lookups are isolated per test.

Production UI wording and lookup behavior were not reverted to satisfy stale tests.

## Relationship and ownership review

This correction changes no Vehicle Rental schema relationship.

The owner boundaries remain:

- Rental owns agreements, Vehicle Use/custody, Running Charts and immutable Rental charge evidence;
- Invoice owns invoice validation/persistence and receivable/payable documents;
- Payment owns payment allocation and instrument lifecycle;
- Finance owns functional/base-currency ledger projections and upgrade backfills;
- Reporting owns generic report presentation/export contracts;
- Configuration remains the shared business-time authority.

No circular dependency, inverse relationship, compatibility layer, duplicate financial state or Rental-owned ledger was added.

## Verification boundary after the correction

The correction was derived directly from the executed failing output and the current authoritative source. The affected source, test contracts, module ownership and final branch diff were re-reviewed through the connected repository.

A post-fix full dependency-backed command run is **not** claimed from this execution environment because its shell still cannot resolve `github.com` and therefore cannot obtain the private repository/dependency tree. GitHub Actions and paid verification services remain excluded by instruction.

The next dependency-backed local verification must rerun, at minimum:

```bash
php artisan test
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
```

Because Finance upgrade migrations changed, the normal migration upgrade/rollback and MySQL/MariaDB verification should also be rerun before deployment.

The pre-fix production build result remains valid evidence only for the pre-fix source; it is not claimed as a post-fix build.

## Conclusion

The supplied verification log identified real release regressions rather than new unimplemented Rental business requirements. They are corrected at their owning boundaries without reopening the closed Rental product-policy ledger, inventing business rules, or restoring legacy Rental code.

The Vehicle Rental business implementation remains complete; release verification is considered corrected in source, while a fresh dependency-backed post-fix run remains required evidence before declaring this exact correction head fully green for deployment.
