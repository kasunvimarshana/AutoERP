# Vehicle Rental authoritative knowledge-base reconciliation — 2026-08-29

## Scope

Re-audited the supplied Vehicle Rental business evidence and reconciled the canonical documentation with the latest authoritative `worktree-0.0.8` implementation baseline.

No Vehicle Rental runtime code was restored or introduced in this change.

## Authoritative evidence audited

### TACGL

- File: `TACGL.zip`
- SHA-256: `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`
- Compressed size: 59,554,116 bytes
- 456 ZIP entries / 452 files / 4 directories
- 420,055,750 uncompressed bytes
- Includes Visual FoxPro DBF/report/application/accounting artifacts.

Structured evidence reviewed includes Vehicle, debtor/customer, creditor/owner, charge, transaction, invoice, debtor/creditor subledger, Finance/GL, account, and error/repair history data.

### Videos

- `1.mp4` — SHA-256 `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf`
- `Recording 2026-06-21 132314.mp4` — SHA-256 `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa`
- `2.mp4` — SHA-256 `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f`
- `ScreenVideo_03-04-2026_18-02-52.mp4` — SHA-256 `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9`

Total represented footage is approximately 1 hour 56 minutes 26 seconds.

The Workshop-focused video is used only for the shared Vehicle availability/maintenance boundary and not as a source for Rental pricing formulas.

## Engineering baseline

- Repository: `kasunvimarshana/AutoERP`
- Authoritative branch: `worktree-0.0.8`
- Baseline HEAD audited before this documentation change: `e8edc66fb7a82bf97176cfa2303c7add1c683952`
- At that baseline there is no active `app/Modules/VehicleRental` runtime module.
- The removed Rental implementation is intentionally not restored, copied, revived, or used as an implementation dependency.

## Business conclusions confirmed

The combined source model is:

```text
One finalized physical Running Chart
    |-- Customer billing — Customer/Lessee Agreement terms
    `-- Owner settlement — Owner/Lessor Agreement terms
```

The two commercial sides are independent. Customer billing does not determine owner payable and owner settlement does not determine customer billing.

The practical source-backed operator flow is kept simple:

```text
Owner Agreement — only for externally supplied vehicles
-> Customer Agreement
-> Select Vehicle
-> Daily Running Chart
-> Customer Invoice / Owner Payable Voucher
-> Customer Receipt / Owner Payment
-> Reports
```

The videos establish Agreements, Running Charts, Monthly/Daily context, AC-rate contexts, with-driver/self-drive behavior, excess distance, driver/OT/night-out concepts, separate customer/owner financial workflows, replacement concept, cheque/reconciliation, and Rental reporting.

TACGL independently establishes recurring vehicle hiring/rental charges, with-driver and self-drive monthly charge vocabulary, excess-charge vocabulary, driver overtime, period/daily-like transaction arithmetic, vehicle-linked customer/creditor financial transactions, and Rental Payment GL activity.

## Important correction to the previous TODO

The previous `docs/vehicle-rental/TODO.md` deliberately used TACGL-only evidence. Under the current instruction the four supplied videos are also authoritative workflow evidence.

Therefore the TODO was corrected so that video-proven workflow concepts are no longer mislabeled as unproven. What remains unproven is the exact financially material **policy/formula** in several areas.

## Unresolved production gates retained

The documentation does not invent:

- partial-month proration;
- monthly day-count convention;
- included/free-KM pooling/reset;
- replacement-period charging;
- downtime deduction;
- garage-mileage billability/payability;
- accident/insurance-excess responsibility;
- deposit application/refund/forfeiture priority;
- exact tax applicability/rounding by Rental component;
- withholding rules;
- exact OT/night-out qualification thresholds;
- mandatory multi-stage Running Chart approval;
- Insurance/Revenue Licence Rental assignment blockers;
- internal transfer-cost policy for company-owned vehicles.

Any production calculation that requires one of these rules must use explicit confirmed configuration/policy or fail closed. It must not silently select a convenient default.

## Files changed

- `docs/knowledgebase.md`
  - refreshed source/engineering authority;
  - consolidated the complete source-backed domain model;
  - documented module ownership, states, validations, edge cases, UI principles, ambiguity register, AI decision protocol, and implementation-readiness gates.

- `docs/vehicle-rental/TODO.md`
  - reconciled TACGL and video authority;
  - replaced the TACGL-only rebuild framing;
  - added a complete prioritized backend/frontend/API/integration/testing/reporting/production backlog;
  - explicitly gates unresolved financial formulas;
  - preserves a simple video-style operator workflow.

- this append-only change record.

## Runtime decision

No speculative Vehicle Rental runtime code was added in this batch.

That is intentional and required by the project rules: the current authoritative branch has no active Rental runtime, and implementing financially material unresolved formulas would require assumptions not supported by TACGL/videos.

The correct next implementation sequence is the release slicing documented in `docs/vehicle-rental/TODO.md`: source-backed operational foundation first, confirmed calculation policies second, financial handoffs third, then deposits/reports and production gates.

## Tooling and safety

- No paid tools used.
- GitHub Actions not used.
- No legacy Rental compatibility patch added.
- No database schema/runtime behavior changed.
- No unrelated module code changed.
