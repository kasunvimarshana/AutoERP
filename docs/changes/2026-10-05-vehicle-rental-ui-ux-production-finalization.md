# Vehicle Rental UI/UX production finalization — 2026-10-05

## Scope

Fresh end-to-end source audit and production-quality UI/UX finalization of the current Vehicle Rental frontend on the authoritative `worktree-0.0.8` lineage.

Starting authoritative release-candidate head:

- `worktree-0.0.8`: `eac9f658ef103f15b6d150eb16f38a05fcd162a9`

Business authority remains TACGL plus the four supplied Vehicle Rental videos. Engineering authority remains root `RULES.md` / `AGENTS.md`. No removed or legacy Rental code was restored, copied, cherry-picked or used as an implementation dependency.

The audit covered every current Vehicle Rental production TSX surface plus Rental navigation/routes and the shared UI foundations used by them:

- Customer and Owner Agreement creation, review, lifecycle, successor, history and terms;
- base-rent preview/billing;
- security deposits;
- Vehicle Use assignment, handover, return, cancellation, replacement, register and history;
- Running Chart entry, lifecycle, register, correction and history;
- customer/owner usage charges and mileage assessment;
- status/loading/empty/error states;
- financial-document handoff wording;
- direct-route authorization;
- page landmarks, spacing, responsive action groups and disclosure patterns;
- configured business-time presentation;
- destructive/corrective action affordances.

No backend business rule, schema, migration, API contract, tax policy, posting rule or Rental calculation formula is changed by this finalization.

## Findings and corrections

### 1. Register pages duplicated the application page landmark and padding

`WorkspaceLayout` already owns the application's main landmark and responsive page padding. Both Rental register pages created another `<main>` with their own `p-4`.

Impact:

- invalid/ambiguous nested main landmarks for assistive technology;
- visually inconsistent extra inset compared with other AutoERP pages.

Correction:

- `VehicleUseRegisterPage` and `RunningChartRegisterPage` now render a normal content container;
- page padding remains owned by `WorkspaceLayout`.

### 2. Register failure/reset behavior could leave stale state behind

The register requests previously retained old rows/meta on a failed reload, and **Clear filters** could clear an error without actually retrying when the current page and applied-filter object were already at their defaults.

Correction:

- failed loads clear rows and pagination metadata;
- clearing filters after an error always performs a clean unfiltered reload;
- stale results are not allowed to reappear as though they matched the latest request.

### 3. Owner financial wording leaked customer-invoice terminology

The business model requires owner-side documents to be presented as **Owner Payable Voucher / Owner Settlement**, not a customer-style invoice.

The UI still contained owner-side fields/actions such as:

- `Invoice date`;
- `Create invoice draft`;
- generic invoice wording in base-rent and usage-charge flows.

Correction:

- added one Rental-owned billing-presentation source for customer vs owner document terminology;
- customer side presents **Customer Invoice**;
- owner side presents **Owner Payable Voucher**;
- date labels, draft action labels, completion messages and explanatory copy use the correct side-specific terminology;
- backend/API field names remain unchanged because Invoice owns the canonical financial document API.

### 4. Owner mileage help text described the customer allowance pool

Mileage assessment text always stated that replacement vehicles share the customer agreement allowance, including while the operator was assessing the Owner side.

That contradicts the documented `commercial_calendar_cycles_v1` policy:

- customer replacement charts under one Customer Agreement share the customer pool;
- each Owner Agreement has its own independent owner allowance pool.

Correction:

- customer and owner mileage explanations are now side-specific;
- Owner Agreement terms now explicitly label `included_km` as **Owner included distance (km)**.

No mileage calculation or allocation logic changed.

### 5. Destructive/corrective actions were not visually distinct

Close, cancel, reverse and void actions could look like ordinary primary/secondary workflow actions.

Correction:

- agreement closure uses the shared danger treatment;
- Vehicle Use cancellation uses the shared danger treatment;
- Running Chart reversal uses the shared danger treatment;
- base-rent and usage-charge void actions use the shared danger treatment;
- final confirmation buttons preserve the same distinction.

Confirmation labels are now explicit, for example:

- `Confirm handover`;
- `Confirm return`;
- `Confirm cancellation`;
- `Confirm finalization`;
- `Confirm reversal`;
- `Confirm closure`.

### 6. Direct Rental URLs did not mirror navigation permissions

Rental navigation correctly hid links based on permissions, but the four direct frontend routes did not use the same `PermissionRoute` guards.

Correction:

- Customer Agreements route -> customer agreement view permission;
- Owner Agreements route -> owner agreement view permission;
- Vehicle Use Register route -> Vehicle Use view permission;
- Running Chart Register route -> Running Chart view permission.

Backend authorization remains authoritative; this closes the frontend navigation/direct-URL consistency gap.

### 7. Checkbox and pagination presentation was inconsistent

Policy-acceptance and company-ownership checkboxes were visually bare compared with the rest of the AutoERP form foundation. Custom charge pagination also used primary-looking navigation buttons.

Correction:

- Rental confirmation/policy checkboxes now use consistent spacing, focus treatment and bordered contextual containers;
- base-rent and usage-charge pagination uses secondary actions and compact page text;
- action groups use wrapping where needed for narrow layouts.

No new shared checkbox abstraction was introduced because the repository does not currently own one and adding a speculative abstraction would overengineer this fix.

## Precision integrity decision

The audit also reviewed whether Rental decimal inputs should be blindly replaced with the existing shared `DecimalInput`.

That change was intentionally **not** made.

The shared control's current presentation formatter limits displayed fractional precision, while Rental financial/source calculations can preserve six-decimal values. Replacing exact-value Rental inputs without a verified precision contract could visually hide meaningful source precision or create a mismatch between displayed and submitted values.

Therefore this UI pass does not trade data integrity for cosmetic formatting. Existing read-only money/quantity surfaces continue to use the shared display formatters, while exact editable source values remain backend-validated.

## Focused regression coverage added/updated

Source tests were updated to cover the proven gaps:

- customer billing action text uses Customer Invoice terminology;
- owner base-rent billing uses Owner Payable Voucher date/action/copy;
- owner usage billing uses Owner Payable Voucher terminology;
- owner mileage explains its independent Owner Agreement allowance and does not show customer-replacement-pool wording;
- owner Agreement history shows the owner-specific included-distance label;
- Vehicle Use Register renders no nested main landmark;
- Running Chart Register renders no nested main landmark;
- both registers retry a clean unfiltered request when **Clear filters** follows a failed load;
- existing customer billing, stale-price, validation, lifecycle and history assertions remain preserved.

## Static verification actually performed

The final branch was re-read and statically scanned after the corrections.

Confirmed:

- no Vehicle Rental production component creates a nested `<main>`;
- all four direct Rental routes have their expected view-permission guards;
- no stale generic `Create invoice draft` action remains in the audited Rental surfaces;
- no incorrect `a Owner Payable Voucher` grammar remains;
- no unconditional owner-side customer replacement allowance wording remains;
- no duplicate import identifier was found in the changed production/test files;
- no Rental-owned browser-timezone source was introduced;
- no `window.alert`, `window.confirm` or `window.prompt` shortcut was introduced;
- no `TODO`, `FIXME`, `HACK` or `XXX` marker was introduced;
- no backend/schema/migration/API/financial-calculation file is part of this UI/UX delta;
- responsive multi-button groups introduced or touched by this pass use wrapping.

## Executable verification boundary

The latest complete dependency-backed application baseline remains the previously recorded green run:

- Laravel: **881 tests / 9,697 assertions passed**;
- TypeScript typecheck: passed;
- ESLint: passed cleanly;
- Vite production build: passed;
- Vitest: **101 / 101 files, 374 / 374 tests passed**.

That baseline predates the October 4 business-time UI delta and this October 5 UI/UX finalization.

The current execution environment still does not contain a complete AutoERP checkout/dependency tree and cannot obtain one through normal GitHub DNS. Hosted GitHub Actions remain excluded by the project's free-tools-only instruction.

Therefore the focused tests added in this change are **test assets reviewed in source, not falsely claimed as executed in this environment**.

The delta is limited to frontend presentation/access-state code, focused frontend tests and documentation. No backend calculation, schema or financial posting behavior is changed.

## Production-readiness decision

Within the repository/source-verification boundary, the fresh Vehicle Rental operator experience is now internally consistent with the canonical business model and the shared AutoERP UI foundation:

- customer and owner commercial sides are visually and linguistically distinct;
- page hierarchy/landmarks are valid;
- direct-route access mirrors navigation permissions;
- loading/error/reset behavior does not present stale data;
- destructive/corrective actions are explicit;
- configured business-time behavior remains shared;
- responsive action groups and financial handoffs are consistent;
- no new legacy workaround or duplicate business rule was introduced.

No additional evidence-backed Vehicle Rental UI/UX gap was found after the final semantic/static pass.
