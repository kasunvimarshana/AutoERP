# Vehicle Rental commercial calendar and closure coverage — 2026-09-25

## Scope

Continuation audit of the fresh Vehicle Rental module against `worktree-0.0.8` review base `8261887e9266ea52ad5fc225c64e4e52dad7563f`, TACGL/video-derived production policies, the canonical knowledge base and the closed acceptance ledger.

No removed legacy Rental code was restored, copied, cherry-picked or used as implementation evidence. No schema, migration, relationship or frontend change was required by this correction.

## Defects found

### Closed open-ended agreements could still create future base-rent coverage

Manual closure preserves the original contractual `ends_on`. For an open-ended agreement that leaves `ends_on = null`. Base-rent preview previously treated `ends_on` as the only upper coverage boundary, so a Closed agreement could preview and create future commercial periods.

### Closed open-ended agreements could still consume future mileage allowance

Mileage allowance/assessment also used only contractual `ends_on`. A Closed open-ended agreement could therefore continue accruing/consuming included-KM allowance and assessing excess distance after lifecycle closure.

### Successor activation used a process-global timezone

The successor effective-day guard used Laravel `app.timezone`. AutoERP tenant configuration explicitly owns the workspace calendar through the namespaced `localization.timezone` definition. A tenant whose local date had already advanced could be incorrectly prevented from activating a successor until UTC/global application midnight.

## Root-cause design

Added `Modules\VehicleRental\Services\RentalCalendar` as the single Rental commercial-calendar boundary service. It:

- resolves the Configuration-owned workspace timezone through `ConfigurationResolverInterface`;
- derives tenant-local civil `today` for successor activation;
- derives effective agreement coverage end as the earlier of contractual `ends_on` and tenant-local lifecycle closure date;
- can reuse an existing mileage-pool timezone snapshot so historical cycle boundaries do not move after configuration changes.

`RentalConfiguration::WORKSPACE_TIMEZONE` is now the single Rental reference to the stable `localization.timezone` configuration contract. The old mileage-specific raw-key constant was removed.

Consumers:

- `BaseRentPreview` enforces effective coverage before calculating; `BaseRentBilling` inherits the same guard through preview.
- `MileageAllowance` caps cycle allowance/assessment at the same effective boundary while retaining an existing pool's snapshotted timezone.
- `AgreementService` uses the tenant/org commercial calendar for successor effective-day activation.

Vehicle Use required no change: planning and handover already require an Active agreement covering the complete selected period, so a Closed agreement cannot create new physical use. Historical financial settlement intentionally permits Closed agreements, but only when their underlying source period remains within the effective commercial boundary.

## Relationship and ownership review

No relationship change was justified.

- Customer Agreement and Owner Agreement remain separate legal/economic aggregates.
- Vehicle Use remains their physical meeting point.
- Running Chart remains attached to Vehicle Use.
- Successor lineage remains one-way `successor -> predecessor`.
- Configuration continues to own tenant/org timezone definition and inheritance; Rental only consumes it.
- Invoice/Payment/Tax/Finance ownership is unchanged.

Adding a second Rental-local timezone setting, duplicating `closed_at` conversion in multiple calculators or rewriting contractual `ends_on` on manual close would create competing sources of truth, so those designs were rejected.

## Regression coverage added

`AgreementClosureCoverageTest` proves:

- an open-ended agreement closed after UTC midnight conversion is capped on the configured `Asia/Colombo` civil date;
- historical base-rent periods through that boundary remain billable;
- later preview/billing is rejected and does not create a Rental charge or Invoice;
- an earlier explicit contract end remains stricter;
- mileage on the closure civil date can be assessed only through that date;
- mileage after the closure boundary is rejected and cannot create a usage charge.

`AgreementTenantCalendarTest` proves:

- at `2026-09-30T19:00:00Z`, when `Asia/Colombo` is already `2026-10-01`, a successor effective `2026-10-01` can activate;
- the predecessor closes at `2026-09-30`.

## External-authority recheck

Official sources were rechecked on 2026-09-25 only as scope/ownership evidence:

- IFRS Foundation, IFRS 15 contract-modification guidance: approved modifications require explicit modification accounting; it does not authorize silently rewriting already-transferred/historical economics.
- Sri Lanka Inland Revenue Department circular index: 2026 publications include updated withholding guidance, reinforcing that tax/withholding treatment is effective-dated legal context owned by Tax/Payment rather than a Rental magic constant.
- MySQL/InnoDB reference manual: `SELECT ... FOR UPDATE` locks are transaction-scoped, supporting the existing transaction/lock discipline around shared mutable Rental state.

No public operator tariff, statutory percentage, withholding threshold, tax rate or GL account was added to Rental from this research.

## Protected backup investigation

Free local inspection of `DATABACKUP/!   CTACGLDATABACKUP202503271759.rar` found 86 listed entries; all require a password and the archive has no comment. Accessible TACGL text/configuration was searched for explicit backup-password/passcode/credential references and yielded no explicit backup credential. Encrypted `password.DBF` / `password.CDX` entries exist inside the protected RAR but cannot be treated as accessible password evidence.

No brute force, dictionary attack, password mutation or unsupported guess was used. The protected backup therefore remains unavailable source evidence and does not alter production rules.

## Verification actually executed in this continuation

The exact current PHP contents were materialized under the local runtime and passed `php -l`:

- `app/Modules/VehicleRental/Constants/RentalConfiguration.php`
- `app/Modules/VehicleRental/Enums/MileagePolicy.php`
- `app/Modules/VehicleRental/Services/RentalCalendar.php`
- `app/Modules/VehicleRental/Services/AgreementService.php`
- `app/Modules/VehicleRental/Services/BaseRentPreview.php`
- `app/Modules/VehicleRental/Services/MileageAllowance.php`
- `app/Modules/VehicleRental/Tests/AgreementClosureCoverageTest.php`
- `app/Modules/VehicleRental/Tests/AgreementTenantCalendarTest.php`

A static continuation scan found no `config('app.timezone', ...)` reference in the changed Rental snippets and only the named `RentalConfiguration::WORKSPACE_TIMEZONE` definition contains the raw `localization.timezone` key.

The current execution environment cannot materialize the complete GitHub checkout because outbound Git/GitHub checkout is DNS-blocked. GitHub Actions were intentionally not used. Therefore Composer/PHPUnit, frontend lint/typecheck/build, MySQL migration/fresh-schema and browser/UAT results are **not** claimed as re-executed for this delta. Existing pre-continuation acceptance evidence remains historical evidence only.

## Final design result

A lifecycle close is now one commercial boundary across base rent and mileage, interpreted by one tenant/org commercial calendar. Historical covered economics remain settleable, future economics cannot leak through an open-ended contractual end, and successor activation no longer depends on a process-global timezone.
