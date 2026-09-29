# Vehicle Rental commercial calendar and closure coverage — 2026-09-25

## Scope

Continuation audit of the fresh Vehicle Rental module against `worktree-0.0.8` review base `8261887e9266ea52ad5fc225c64e4e52dad7563f`, TACGL/video-derived production policies, the canonical knowledge base and the closed acceptance ledger.

No removed legacy Rental code was restored, copied, cherry-picked or used as implementation evidence.

## Defects found

### Closed open-ended agreements could still create future base-rent coverage

Manual closure preserves the original contractual `ends_on`. For an open-ended agreement that leaves `ends_on = null`. Base-rent preview previously treated `ends_on` as the only upper coverage boundary, so a Closed agreement could preview and create future commercial periods.

### Closed open-ended agreements could still consume future mileage allowance

Mileage allowance/assessment also used only contractual `ends_on`. A Closed open-ended agreement could therefore continue accruing/consuming included-KM allowance and assessing excess distance after lifecycle closure.

### Successor activation used a process-global timezone

The successor effective-day guard used Laravel `app.timezone`. AutoERP tenant configuration explicitly owns the workspace calendar through the namespaced `localization.timezone` definition. A tenant whose local date had already advanced could be incorrectly prevented from activating a successor until UTC/global application midnight.

### Recomputing a historical close day from `closed_at` was unstable

The first closure correction derived the lifecycle civil date from the stored UTC close instant plus the **current** workspace timezone. That still allowed a later timezone configuration change to move a historical commercial boundary by a calendar day.

A historical financial boundary must be a recorded fact, not a future reinterpretation of an instant under mutable configuration.

## Root-cause design

`Modules\VehicleRental\Services\RentalCalendar` is the single Rental commercial-calendar boundary service. It:

- resolves the Configuration-owned workspace timezone through `ConfigurationResolverInterface`;
- derives tenant-local civil `today` for successor activation;
- derives a civil date from a lifecycle instant only at the moment the lifecycle event occurs;
- derives effective agreement coverage as the earlier of contractual `ends_on` and immutable `closed_on`.

`RentalConfiguration::WORKSPACE_TIMEZONE` is the single Rental reference to the stable `localization.timezone` configuration contract. The old mileage-specific raw-key constant was removed.

### Immutable closure snapshot

Customer and Owner agreements now persist three deliberately different concepts:

- `ends_on` — contractual/effective commercial term boundary;
- `closed_at` — audit instant;
- `closed_on` — immutable tenant/org civil date captured when the lifecycle moves Active -> Closed.

`AgreementService` captures `closed_at` once, derives `closed_on` from that same instant through the Configuration-owned timezone, and stores both in the same transaction. Successor activation uses the same path; the predecessor may have `ends_on` equal to the day before the successor while `closed_on` records the actual local lifecycle day.

`Agreement` model immutability prevents rewriting `closed_at`, `closed_on`, `ends_on` or commercial terms after closure.

An additive Vehicle Rental migration adds nullable `closed_on` columns to both fresh agreement tables. Existing closed rows are backfilled once from `closed_at` using the effective tenant/org workspace timezone available at migration time and are thereafter frozen. This is the best reconstructable historical civil date because the old schema did not persist a closure-day snapshot. No historical timestamp or contract term is rewritten.

Consumers:

- `BaseRentPreview` enforces effective coverage before calculating; `BaseRentBilling` inherits the same guard through preview.
- `MileageAllowance` caps cycle allowance/assessment at the same immutable effective boundary while retaining an existing pool's snapshotted timezone for cycle interpretation.
- `AgreementService` uses the tenant/org commercial calendar for successor effective-day activation.
- `AgreementResource` exposes `closed_on` so API clients can distinguish contractual term, lifecycle instant and lifecycle civil day.

Vehicle Use required no change: planning and handover already require an Active agreement covering the complete selected period, so a Closed agreement cannot create new physical use. Historical financial settlement intentionally permits Closed agreements, but only when the underlying source period remains within the effective commercial boundary.

## Relationship and ownership review

No relationship change was justified.

- Customer Agreement and Owner Agreement remain separate legal/economic aggregates.
- Vehicle Use remains their physical meeting point.
- Running Chart remains attached to Vehicle Use.
- Successor lineage remains one-way `successor -> predecessor`.
- Configuration continues to own tenant/org timezone definition and inheritance; Rental only consumes it.
- Invoice/Payment/Tax/Finance ownership is unchanged.

The only schema addition is the scalar `closed_on` historical snapshot owned by each Rental agreement. It does not create a new dependency or bidirectional relationship. Recomputing closure dates indefinitely from mutable configuration, adding a second Rental-local timezone setting, duplicating closure conversion in calculators or rewriting contractual `ends_on` on manual close were rejected because they create competing sources of truth.

## Regression coverage added

`AgreementClosureCoverageTest` proves:

- an open-ended agreement closed after UTC/local-day conversion stores the configured `Asia/Colombo` `closed_on` date;
- historical base-rent periods through that boundary remain billable;
- later preview/billing is rejected and does not create a Rental charge or Invoice;
- an earlier explicit contract end remains stricter;
- mileage on the closure civil date can be assessed only through that date;
- mileage after the closure boundary is rejected and cannot create a usage charge;
- changing the workspace timezone after closure does not move the stored commercial boundary.

`AgreementTenantCalendarTest` proves:

- at `2026-09-30T19:00:00Z`, when `Asia/Colombo` is already `2026-10-01`, a successor effective `2026-10-01` can activate;
- the predecessor commercial `ends_on` becomes `2026-09-30` while lifecycle `closed_on` is `2026-10-01`.

`AgreementImmutabilityTest` additionally proves that a recorded closure date cannot be rewritten after the transition.

## External-authority recheck

Official sources were rechecked on 2026-09-25 only as scope/ownership evidence:

- IFRS Foundation, IFRS 16 lease-modification guidance: lease modification accounting is classification- and fact-specific. It supports preserving explicit modification/effective-date lineage where IFRS 16 applies; it is not used here as a universal Vehicle Rental tariff or as a substitute for determining whether a specific AutoERP arrangement is a lease, service, or mixed contract.
- IFRS 15 was also reviewed only to confirm that it is not a universal Vehicle Rental accounting basis; lease contracts are outside its general revenue-contract scope. No Rental runtime rule is derived from IFRS 15.
- Sri Lanka Inland Revenue Department circular index: 2026 publications include updated withholding and invoice-format guidance, reinforcing that tax/withholding treatment is effective-dated legal context owned by Tax/Payment rather than a Rental magic constant.
- MySQL/InnoDB reference manual: `SELECT ... FOR UPDATE` locks are transaction-scoped, supporting the existing transaction/lock discipline around shared mutable Rental state.

No public operator tariff, statutory percentage, withholding threshold, tax rate or GL account was added to Rental from this research.

## Protected backup investigation

Free local inspection of `DATABACKUP/!   CTACGLDATABACKUP202503271759.rar` found 86 listed entries; all require a password and the archive has no comment. Accessible TACGL text/configuration was searched for explicit backup-password/passcode/credential references and yielded no explicit backup credential. Encrypted `password.DBF` / `password.CDX` entries exist inside the protected RAR but cannot be treated as accessible password evidence.

No brute force, dictionary attack, password mutation or unsupported guess was used. The protected backup therefore remains unavailable source evidence and does not alter production rules.

## Verification actually executed in this continuation

- Exact final PHP contents were materialized for the additive migration, `RentalConfiguration`, `MileagePolicy`, `RentalCalendar`, `Agreement` and `AgreementResource`; all passed `php -l` under PHP 8.4.23.
- The exact final `resources/js/modules/vehicle-rental/agreements.ts` delta parsed successfully with TypeScript 5.8.3.
- The pre-snapshot continuation versions of `AgreementService`, `BaseRentPreview`, `MileageAllowance`, `AgreementClosureCoverageTest` and `AgreementTenantCalendarTest` had passed `php -l`; their final changed hunks were subsequently reviewed in the PR diff. Full dependency-backed execution of those tests is not claimed.

Static review verifies:

- no process-global `app.timezone` remains in the changed Rental commercial-day path;
- the raw `localization.timezone` key remains centralized behind `RentalConfiguration::WORKSPACE_TIMEZONE`;
- base rent and mileage use the same immutable agreement coverage boundary;
- the single schema addition is `closed_on`; no new relationship, Rental tax rate, GL account or legacy implementation was introduced.

The current execution environment cannot materialize the complete GitHub checkout because outbound Git/GitHub checkout is DNS-blocked. GitHub Actions are intentionally not used. Therefore Composer/PHPUnit, frontend lint/typecheck/build, MySQL migration/fresh-schema and browser/UAT results are not claimed as re-executed for this exact continuation delta. Existing pre-continuation acceptance evidence remains historical evidence only.

## Final design result

A lifecycle close is now one immutable commercial boundary across base rent and mileage. Historical covered economics remain settleable, future economics cannot leak through an open-ended contractual end, successor activation follows the tenant/org commercial calendar, and later timezone configuration changes cannot reinterpret already-recorded agreement closure history.
