# Vehicle Rental UI/UX finalization — 2026-10-02

## Scope

Finalize the fresh Vehicle Rental operator experience on the latest authoritative `worktree-0.0.8` without changing Rental business rules, financial formulas, schema ownership, or module boundaries.

Business/UI authority remains:

- TACGL for Vehicle Rental business meaning;
- the four supplied Vehicle Rental videos for practical workflow evidence;
- `docs/knowledgebase.md` for the reconciled production-policy contract;
- `worktree-0.0.8` for implementation authority.

No removed/legacy Rental code was restored, copied or referenced.

## UX audit conclusion

The existing workflow was functionally complete, but a final operator-facing audit found concrete presentation and interaction issues rather than missing domain behavior:

1. company-owned Vehicle Use could present an enabled submit action before a planned handover/start time was entered;
2. replacement reason and replacement odometer validation feedback was not mapped to the most relevant fields;
3. custody handover/return confirmation could appear available before the required actual event time was entered;
4. Running Chart OT-minute and night-out facts were integer backend values but used generic decimal-style inputs;
5. Vehicle Use / Running Chart register filters lacked a direct reset action and used inconsistent shared page/filter presentation;
6. several disclosure actions did not expose their expanded state;
7. agreement terms, odometers, distances, base-rent amounts, usage charges, mileage values and deposit/payment balances exposed six-decimal storage precision directly;
8. base-rent, usage-charge, mileage and deposit areas did not consistently expose explicit loading/empty states;
9. the Vehicle Rental sidebar listed operational registers before agreements even though the canonical workflow is agreement-first;
10. one Running Chart empty distance state rendered awkwardly as `Not recorded km`.

These are UI/UX defects only. No evidence justified a new commercial rule, monetary formula, relationship, backend workflow or schema change.

## Implemented changes

### Agreement review

- Reused `StatusBadge`, `MoneyDisplay` and `QuantityDisplay`.
- Displayed agreement status consistently.
- Presented terms in a readable summary grid.
- Kept unknown values as `Not specified`.
- Presented included kilometres as a quantity and monetary agreement terms using the agreement currency.
- Exposed disclosure state for deposits, assigned vehicles and agreement history.

### Vehicle Use

- Standardized the register with shared `ContentHeader`, `Panel`, `Select`, `LoadingState` and `StatusBadge`.
- Added explicit clear-filter behavior without causing an idle loading state when the register is already unfiltered.
- Formatted odometers through `QuantityDisplay`.
- Added accessible detail/history/Running Chart expanded state.
- Prevented assignment submission until a planned handover/start is entered, including company-owned vehicle assignments.
- Prevented replacement confirmation until the required replacement reason is present.
- Mapped replacement return/handover odometer and reason validation errors to the relevant inputs.
- Prevented handover/return confirmation until the actual event time is entered.
- Used the existing named `OPERATIONAL_TIME_STEP_SECONDS` constant; no new magic time-step value was introduced.

### Running Chart

- Standardized the register with shared page/filter/loading/status components.
- Added safe clear-filter behavior and accessible detail/history disclosure state.
- Presented total distance and recorded measurements with `QuantityDisplay`.
- Kept unknown measurements as `Not recorded`; explicit zero remains visibly zero.
- Corrected the empty total-distance presentation so it no longer appends `km` to `Not recorded`.
- Kept odometer/distance inputs decimal.
- Changed Normal/Double/Triple OT minutes and night-outs to integer inputs with minimum zero and step one, matching backend fact types.
- Reused shared `Select` for AC mode.
- Surfaced field-level AC, driver-observation and notes validation errors.

### Billing, mileage and deposits

- Reused the shared `useApi` request lifecycle for base-charge lists, usage charges, mileage quotes and deposit summaries.
- Added explicit loading states and meaningful empty states.
- Removed incomplete/undefined charge context during request loading.
- Made customer-charge versus owner-payable selection visually explicit while keeping the two financial sides independent.
- Reused `MoneyDisplay` and `QuantityDisplay` throughout operator-facing amounts and measurements.
- Preserved exact backend calculation precision; only presentation formatting changed.
- Kept exchange-rate inputs explicit and decimal-capable.
- Preserved Invoice/Payment/Tax/Finance ownership and all existing source/reissue/void/idempotency rules.

### Navigation

Vehicle Rental navigation now follows the canonical operator workflow:

1. Owner Agreements
2. Customer Agreements
3. Vehicle Use Register
4. Running Chart Register

This is a presentation/order change only. Existing permission, module-entitlement and router contracts remain unchanged.

## Regression coverage added or updated

Focused frontend tests now cover:

- formatted agreement financial terms and included distance;
- company-owned assignment requiring a planned handover time;
- replacement confirmation requiring a reason;
- custody handover requiring an actual event time;
- integer Running Chart OT/night-out controls while distances remain decimal;
- Vehicle Use filter reset and formatted odometer display;
- Running Chart filter reset and formatted measurement display;
- usage-charge loading without incomplete/undefined agreement context;
- empty deposit history;
- formatted base-rent preview output;
- formatted mileage quote output;
- agreement-first Rental navigation order;
- existing status/distance assertions updated for the shared presentation components.

No backend test was weakened or removed.

## Static verification performed on this branch

Because the available execution container has no AutoERP checkout/dependency tree and cannot resolve either `github.com` or `registry.npmjs.org`, a dependency-backed Vitest/typecheck/lint/build run cannot be executed from this environment without hosted CI. GitHub Actions remain intentionally unused under the project's free-tools-only rule.

The following checks were performed directly against the branch source:

- reviewed every changed source/test file against the authoritative base;
- reviewed the two highest-risk request-lifecycle refactors (`UsageChargePanel` and `MileageAssessmentPanel`) end-to-end;
- verified the refactors continue to use server-returned agreement/quote revisions and do not move calculation ownership into the frontend;
- scanned modified TypeScript/TSX imports and found no unused-import candidate;
- scanned modified production source for `TODO`, `FIXME`, `HACK`, `XXX` markers: none introduced;
- confirmed no hardcoded `step={60}` remains; operational time inputs use the named Rental constant;
- confirmed no backend, migration, schema or financial-calculation file is part of this UI/UX delta;
- checked router and permission-gated Rental navigation discoverability.

## Executed baseline immediately before this UI delta

The dependency-backed application verification executed before this UI/UX branch remains:

- Laravel: **881 tests / 9,697 assertions passed**;
- TypeScript typecheck: passed;
- ESLint: passed with no errors or warnings;
- Vite production build: passed with 693 transformed modules;
- Vitest: **101 / 101 test files**, **374 / 374 tests passed**.

That executed run proves the pre-change application baseline. It must not be misrepresented as a post-change run of this UI/UX delta.

## Architecture / security / integrity review

- No API contract or authorization was weakened.
- No trusted tenant/org/user context moved to client ownership.
- No financial calculation moved to the frontend.
- No customer/owner independence rule changed.
- No automatic monetary effect was added.
- No schema or relationship changed.
- No legacy Rental implementation was reintroduced.
- No compatibility patch was added.
- All changes remain in the frontend/navigation layer that owns operator presentation.

## Release decision

The UI/UX delta is intentionally limited to operator presentation, interaction guards and focused regression tests. It does not alter backend/domain behavior.

The branch is suitable for repository integration after final diff/mergeability review. A post-change dependency-backed test execution must be recorded when an environment with the repository dependencies is available; this record does not fabricate such a result.
