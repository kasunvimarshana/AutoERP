# Vehicle Rental end-to-end UI/UX audit completion — 2026-10-02

## Scope

Second end-to-end UI/UX audit of the fresh Vehicle Rental implementation on the live authoritative `worktree-0.0.8` branch after the initial UI/UX finalization.

This audit intentionally re-read the remaining operator surfaces rather than assuming the prior release was complete:

- Customer and Owner Agreement creation/review/revision/history;
- Vehicle Use assignment, custody, replacement, register and history;
- Running Chart entry, register, lifecycle, correction and history;
- base-rent preview/billing;
- mileage assessment;
- OT/night-out usage billing;
- deposit receipt context;
- Rental navigation and shared status/loading/presentation primitives;
- focused frontend regression coverage.

No legacy Rental runtime was restored or referenced. No new commercial rule, financial formula, schema relationship, permission model or backend business behavior was introduced.

## Evidence and source-contract checks

The audit cross-checked the frontend against the current backend resources/services before changing UI behavior.

Verified backend history contracts:

- Agreement history already returns status, basis, driver mode, notes, reason, exact terms and snapshots.
- Vehicle Use history returns immutable custody status, period and odometer snapshots.
- Running Chart history returns all mutable evidence facts, including distance/count observations, AC mode, driver identity snapshots, driver observation and notes.
- employee Running Chart identity stores immutable name/reference snapshots.

Verified financial validation contracts:

- Rental financial document input validates invoice date, due date and exchange rate server-side.
- usage-charge component validation is returned against `component`.
- deposit receipt validates payment date and exchange rate.
- mileage assessment validates document inputs even when the calculated amount is zero; a zero assessment records allowance usage but does not create an invoice.

These contracts were used to correct frontend presentation only. No missing business rule was inferred.

## Findings and fixes

### 1. Agreement history was incomplete and commercially misleading

Problem:

- owner history reused generic customer term labels;
- customer-only security-deposit terms could appear in Owner Agreement history;
- storage precision was exposed;
- backend-provided status, basis, driver arrangement and notes were omitted;
- no explicit loading or empty state existed.

Fix:

- added one shared `AgreementTermsGrid` as the single presentation source for current and historical terms;
- preserved owner-specific labels through `termLabel()`;
- applied `visibleTermKeys()`, so customer-only deposit terms never appear on the owner side;
- formatted money and quantities through shared formatters;
- displayed immutable status, basis, driver arrangement, notes, reason and period evidence;
- added typed `AgreementHistory` and moved history loading into the owning `agreementApi`;
- added loading, error, empty and pagination states.

### 2. Vehicle Use and Running Chart history lagged behind the main screens

Problem:

- raw six-decimal odometer/distance values;
- no loading/empty state;
- raw action strings;
- Vehicle Use history did not use the business label `With customer`;
- Running Chart history omitted most recorded evidence and driver snapshots.

Fix:

- formatted quantities consistently;
- humanized actions;
- added explicit loading/error/empty states;
- displayed Vehicle Use status using `USE_LABELS`;
- displayed complete Running Chart revision evidence: odometers, garage/commercial KM, OT/night-outs, AC mode, immutable employee/external driver snapshots, driver observation and notes.

### 3. Failed reloads could leave stale rows looking current

Problem:

Agreement, assigned-vehicle and Running Chart panels retained old rows when a subsequent fetch failed.

Fix:

- failed list requests now clear rows and pagination metadata;
- stale rows are not rendered underneath an error;
- focused regression tests prove old content disappears after a failed reload.

### 4. Narrative evidence fields used narrow single-line inputs

Problem:

Notes and lifecycle reasons are business evidence and can be longer than a short code/value, but several screens used standard one-line inputs.

Fix:

- reused the shared `Textarea` for Agreement notes, successor revision reason, closure reason, Vehicle Use assignment/replacement notes, custody action reason, Running Chart driver observation/notes, reversal reason and charge void reason;
- no field length or backend validation rule changed.

### 5. Financial validation feedback was not attached to the corrective field

Problem:

Server-side validation was surfaced in the general error alert but invoice/payment date, exchange-rate, component and void-reason inputs did not consistently show the corresponding field message.

Fix:

- mapped `from`, `until`, `invoice_date`, `due_date`, `exchange_rate`, `component`, `reason`, `payment_date` to their owning controls;
- preserved the general error alert;
- no client calculation or validation authority was added.

### 6. Financial history/status presentation was inconsistent

Problem:

Invoice and payment document/posting statuses appeared as raw strings and one usage-rate quote still exposed storage precision.

Fix:

- reused `StatusBadge` for linked financial statuses;
- extended the shared badge with an optional presentation label while preserving its status-based styling source;
- reused `MoneyDisplay` for the usage quote rate.

### 7. Vehicle Use business status semantics were lost by generic humanization

Problem:

`in_custody` is intentionally labelled `With customer` in Rental, but generic status humanization displayed `In Custody`.

Fix:

- `StatusBadge` now accepts an optional business display label;
- Vehicle Use current/register/history surfaces pass `USE_LABELS[row.status]`;
- shared status styling remains centralized.

### 8. Running Chart driver selector rendered duplicate empty choices

Problem:

the shared `Select` already supplies a placeholder, while the Running Chart option collection also contained its own empty `Not recorded` option.

Fix:

- removed the duplicate empty option;
- set the shared Select placeholder to `Not recorded`;
- regression coverage asserts exactly one empty option.

### 9. Mileage helper text contradicted the backend contract

Problem:

the UI said document inputs apply only when an amount is due, while the backend validates them for every assessment before deciding whether an invoice is created.

Fix:

- corrected the explanation to match the actual service contract;
- zero assessment behavior itself was not changed.

### 10. Base-rent charge-list failure could look like a legitimate empty history

Problem:

a failed charge-list request could combine an error with an empty-list message, and the create action could otherwise be attempted before reviewed charge history was available.

Fix:

- billing submit remains disabled while charge history is loading or failed;
- failed history no longer renders `No base-rent charges have been recorded`;
- test coverage locks this safety behavior.

### 11. Responsive and table consistency

- action groups now wrap cleanly on narrow screens;
- the base-rent period calculation table has consistent caption/header/cell spacing and row separators;
- no page hierarchy or business workflow was redesigned unnecessarily.

## Regression coverage

New focused tests cover:

- complete Owner Agreement history and correct term visibility/labels;
- Agreement History loading state;
- Vehicle Use history business status, open-ended period and formatted odometer;
- Running Chart history complete formatted evidence;
- immutable employee and external driver identity snapshots;
- business-specific `StatusBadge` labels;
- duplicate Driver identity placeholder prevention;
- stale Agreement/Vehicle Use/Running Chart row suppression after failed reload;
- field-level base-rent, usage-charge, mileage and deposit validation feedback;
- formatted usage rate;
- failed base-charge list not appearing as a valid empty list and billing remaining blocked.

Existing lifecycle and financial tests were retained.

## Static verification performed on this branch

The available execution container still does not contain the AutoERP repository dependency tree. Hosted GitHub Actions remain excluded by the project instruction.

The following source-level gates were executed against the branch:

- re-read high-risk parent JSX after edits;
- caught and corrected one missing action-wrapper closing element before release;
- scanned changed production TypeScript/TSX files for unused import candidates: none found in completed batches;
- scanned changed production source for `TODO`, `FIXME`, `HACK`, `XXX`: none introduced;
- scanned changed production source for hardcoded six-decimal presentation literals: none found;
- reviewed backend resource/service contracts for every frontend behavior changed;
- confirmed no backend module, schema, migration, tax, finance or commercial-calculation change belongs to this delta.

The latest dependency-backed full application gate preceding this audit remains:

- Laravel: **881 tests / 9,697 assertions passed**;
- TypeScript typecheck: passed;
- ESLint: passed with no errors or warnings;
- Vite production build: passed;
- frontend Vitest: **101 / 101 test files, 374 / 374 tests passed**.

That is the executed baseline, not a fabricated post-change result. New focused tests are committed for this audit delta and require a dependency-backed rerun when such an environment is available.

## Architecture and ownership result

- Rental keeps only Rental-owned operator workflow/presentation changes.
- Customer/Supplier/Vehicle/HR identity ownership is unchanged.
- Invoice/Payment/Tax/Finance lifecycle and calculations remain in their owner modules.
- no relationship or schema change was required.
- no duplicate financial state was introduced.
- no client-side business calculation was added.
- no unsupported commercial rule was invented.
- no compatibility patch or legacy implementation was restored.

## Release decision

The evidence-backed remaining UI/UX gaps identified by this second pass are addressed in the frontend/shared-presentation layer that owns them.

Repository promotion is appropriate after final branch-diff, mergeability and source/static verification. This record does not claim a live hosting deployment, live production database operation or human browser UAT.
