# Vehicle Rental video + TACGL canonical knowledge refresh — 2026-08-29

## Purpose

Refresh the canonical `docs/knowledgebase.md` after re-validating the complete Vehicle Rental evidence set: all four authoritative Vehicle Rental videos, the latest re-supplied TACGL archive, and the current `worktree-0.0.8` engineering state.

This change is documentation-only. It does not reactivate or reintroduce the retired Vehicle Rental runtime.

## Source verification

### Video evidence

The authoritative Vehicle Rental video set remains:

- `1.mp4` — approximately 40:50;
- `Recording 2026-06-21 132314.mp4` — approximately 41:58;
- `2.mp4` — approximately 21:14;
- `ScreenVideo_03-04-2026_18-02-52.mp4` — approximately 12:24.

Total represented footage is approximately 1 hour 56 minutes 26 seconds. Existing end-to-end timeline audits were re-read and cross-checked. The fourth recording remains supporting evidence for shared Vehicle/Workshop availability and must not be used to invent Rental pricing rules.

### TACGL evidence

Latest re-supply audited: `TACGL(10).zip`.

- SHA-256: `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`;
- compressed size: 59,554,116 bytes;
- archive entries: 456;
- non-directory files: 452;
- directories: 4;
- uncompressed size: 420,055,750 bytes;
- 114 DBF, 109 FRT, 109 FRX, 46 IDX, 32 CDX, 16 PDF and 5 XLS files.

The hash exactly matches the previously audited canonical TACGL corpus. `TACGL(10).zip` is therefore a re-supply of the same evidence, not an independent corroborating dataset.

### Engineering source

The authoritative `worktree-0.0.8` source branch was verified before writing at:

`dee566f982e5fd6fec001b8c09b7ffd41676805e` — `Deepen Vehicle Rental TACGL knowledge`.

At that source state:

- the active `app/Modules/VehicleRental` runtime is absent;
- Vehicle and Vehicle Service remain active;
- Vehicle exposes `VehicleAvailabilityBlockerInterface` as the shared availability boundary.

## Reconfirmed business evidence

The 2026-08-29 TACGL re-audit reproduced the previously captured high-value evidence, including:

- exact customer Rental lineage `LCH2005407 -> INV2005519 -> Debtor subledger -> REC2003089 allocation -> GL`;
- `OWN...` with `TXNTYPE = 2` means Outside Work, not Owner/Lessor;
- `7048-000 RENTAL PAYMENT` has 25 positive debit rows across 21 `PRB...` payment vouchers in the inspected corpus;
- six normalized duplicate vehicle-registration groups, demonstrating why commercial context must not be represented by duplicate physical Vehicle records;
- all 1,076 active `scfveh` rows carry `VEHTYP = 03`, making that legacy field unsuitable as authoritative ownership truth;
- non-calendar monthly cycles such as `25 -> 24` and `18 -> 17`;
- fixed-30-day proration arithmetic precedents on both customer and owner/payment sides without promoting them to a universal rule;
- third-party hire/Outside Work cost and customer-recovery evidence;
- deleted/replaced Rental transaction evidence requiring immutable correction/reversal lineage;
- Rental charges posted through legacy Workshop/Pending Jobs accounts, which is evidence of economic activity but not target architecture;
- free-text quantity/rate narratives including at least one narrative-versus-stored-amount mismatch, confirming that structured calculation facts must be authoritative;
- 16 PDF exports / 63 pages, predominantly Debtor Outstanding Age Analysis, plus one empty-page PDF artifact; no new Rental pricing formula was established.

## Canonical domain conclusions retained

- One physical Daily Running Chart is shared operational evidence.
- Customer billing and owner settlement are independent commercial calculations.
- Lessee Agreement/rates determine customer charges.
- Lessor Agreement/rates determine owner payable.
- One physical vehicle has one stable Vehicle identity.
- Supply/use relationships and allocations are effective-dated.
- Same finalized usage cannot be consumed twice on the same commercial side.
- Finalized operational evidence and posted financial history are immutable and corrected through explicit lineage/reversal.
- Cross-module rules remain with their owner and are consumed through explicit contracts.
- Predefined option sets, shared immutable values and environment/changing settings must use the appropriate enum, constant or configuration source instead of embedded magic literals.

## Architecture boundary

No legacy design flaw is adopted. In particular this refresh does not restore or endorse:

- duplicate physical Vehicle records for customer/owner context;
- Rental embedded as Vehicle Service Labour or Outside Work;
- Rental revenue under Workshop sales accounts;
- direct free-text Rental expense vouchers as the ordinary owner-settlement model;
- free-text calculation descriptions as quantity/rate authority;
- mutable/deleted-record mirrors as the target correction-history strategy;
- duplicated settlement engines for vehicle-owner versus leasing-company classifications;
- raw GL/internal IDs as ordinary Rental-operator inputs.

The current Vehicle availability contract remains the appropriate integration boundary for Workshop/off-road/Rental conflicts. Each module retains ownership of its own blocking business state.

## Policy boundary

The following remain explicit `Needs business confirmation` items and must not be hardcoded or inferred from isolated legacy transactions:

- universal partial-period proration policy;
- included-KM pooling/reset/carry-forward;
- replacement-vehicle charging;
- downtime charging/deductions;
- garage/internal mileage treatment;
- driver/OT/night-out qualification and rounding;
- fuel/repair responsibility;
- accident/insurance-excess responsibility;
- deposit application/refund/forfeiture;
- exact tax/withholding/rounding order;
- maker-checker, approval thresholds and segregation of duties;
- reservation, condition-photo, fuel-level, signature, credit-limit-block and notification policies not proven by the recordings.

## Files changed

- `docs/knowledgebase.md`
- `docs/changes/2026-08-29-vehicle-rental-video-tacgl-canonical-knowledge-refresh.md`

## Verification

- No PHP/TypeScript production runtime file was intentionally changed.
- No migration, route, provider, tenant feature, Finance seed or test was intentionally changed.
- No Vehicle Rental runtime was restored.
- No paid tool or GitHub Actions workflow was used.
- The refresh treats identical TACGL hashes as one evidence corpus rather than manufacturing additional confidence from duplicate uploads.
