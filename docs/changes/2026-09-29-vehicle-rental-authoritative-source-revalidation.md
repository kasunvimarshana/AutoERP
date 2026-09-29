# Vehicle Rental authoritative-source revalidation

Date: 2026-09-29

## Scope

Revalidated the current `worktree-0.0.8` Vehicle Rental business/domain documentation and critical implementation boundaries against the authoritative TACGL/video source set supplied for this continuation task.

This is a revalidation record, not a new business-rule invention or a legacy-Rental restoration.

## Authoritative source identity

The currently supplied source files that were available for byte-level verification match the canonical source identities already recorded in `docs/knowledgebase.md`:

- `TACGL.zip` — SHA-256 `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`; 452 non-directory business files.
- `1.mp4` — SHA-256 `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf`; duration approximately 40:50.
- `Recording 2026-06-21 132314.mp4` — SHA-256 `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa`; duration approximately 41:58.
- `ScreenVideo_03-04-2026_18-02-52.mp4` — SHA-256 `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9`; duration approximately 12:24.
- `2.mp4` — SHA-256 `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f`; duration approximately 21:14.

The separately uploaded `TACGL.rar` was not present in the mounted working workspace at the final byte-level check. No new claim is made from inaccessible bytes. The existing canonical knowledge base records the earlier reconciliation of `TACGL.rar` to the same business corpus; this continuation preserves that evidence rather than guessing.

The ZIP still includes the password-protected dated TACGL backup previously documented by the acceptance ledger. No brute-force or unsupported password guessing was performed.

## Repository baseline

Revalidation used `worktree-0.0.8` at commit:

`8de690c4d433e10dd4a948b1023cf93572e80d6b`

The branch contains the fresh `VehicleRental` module and no `app/Modules/Rental` runtime. The existing `docs/vehicle-rental/TODO.md` remains a closed acceptance ledger with no open product-policy TODO items.

## Business/domain conclusion

The supplied authoritative corpus is byte-identical to the corpus already used to build the canonical `docs/knowledgebase.md`. No new TACGL/video evidence was found that changes the established domain model, workflow, state model, or production policy.

The following principles therefore remain authoritative:

1. Customer and Owner/Lessor agreements are independent legal/economic aggregates.
2. Vehicle Use records physical assignment/custody and may reference an Owner source when the vehicle is externally supplied.
3. Running Chart records immutable physical usage evidence with explicit reverse/correction lineage.
4. Replacement preserves physical vehicle history and does not by itself create a surcharge, duplicate base rent, credit, or downtime deduction.
5. Customer billing and Owner settlement consume the same physical evidence independently; one side is never derived from the other.
6. Commercial calendar decisions use the Configuration-owned tenant/org timezone.
7. Automatic Rental money must stay inside effective agreement commercial coverage.
8. Invoice, Payment, Tax, Finance, HR and Vehicle Service retain ownership of their respective responsibilities; Rental does not duplicate their ledgers or business rules.
9. Historical examples that do not prove a universal formula remain observations, not automatic pricing rules.
10. Where TACGL/video evidence does not prove a monetary formula, Rental creates no automatic monetary effect. Any authorized exception must use an explicit governed adjustment in the owning financial capability.

## Ambiguous/unproven policy treatment

The prior evidence classification remains correct. Replacement charging, downtime deductions, deposit forfeiture/application priority, refund behavior, statutory tax/withholding percentages or thresholds, AC charging, driver-amount proration, fuel/miscellaneous recovery and other exceptional deductions must not be guessed from isolated historical examples.

Where a named/configured production policy already exists in the knowledge base, that policy remains the deterministic implementation rule. Where no such policy exists, the safe rule remains no automatic financial effect plus explicit audited financial adjustment when authorized.

## Critical implementation revalidation

Focused source inspection confirms the current implementation still matches the canonical policy at the high-risk boundaries:

- `RentalCalendar` resolves tenant/org commercial civil dates and immutable agreement coverage end.
- `VehicleUseService` performs version checks, row/vehicle locks, agreement/source coverage validation, shared vehicle-availability checks, custody transitions, odometer continuity, replacement lineage and transactional state changes.
- `RunningChartService` keeps finalized evidence immutable, locks concurrent driver/vehicle timelines, enforces actual-custody coverage, validates odometer continuity, and requires downstream charge release before reversal.
- `RentalChargeDocuments` delegates document/tax/posting creation to the owning Invoice/Tax/Finance capabilities, preserves Rental source lineage, version-checks billing, and refuses automatic billing outside agreement commercial coverage.
- The existing acceptance ledger documents the dedicated tests covering successor lineage, timezone boundaries, source eligibility, immutable closure dates, physical overrun versus commercial entitlement, driver conflicts and financial coverage.

No evidence-backed code correction was identified during this continuation audit. Introducing another implementation change would therefore create unnecessary regression risk and would violate the project rule to make only required changes.

## Verification evidence

Executed during this continuation:

- direct SHA-256 verification of the available TACGL ZIP and all four supplied videos;
- direct video duration verification with local `ffprobe`;
- ZIP inventory verification confirming 452 non-directory files;
- re-read of canonical `docs/knowledgebase.md` and `docs/vehicle-rental/TODO.md` on the target branch;
- re-read of the latest Vehicle Rental change record;
- focused source inspection of `RentalCalendar`, `VehicleUseService`, `RunningChartService` and `RentalChargeDocuments`;
- current target-branch head and commit-status inspection.

The target head has no combined-status contexts reported by GitHub. No GitHub Actions or paid tooling was used as substitute verification.

Because no runtime code changed in this continuation, no new PHPUnit/frontend/migration pass is claimed. Existing 2026-09-26 execution limitations and syntax-check evidence remain documented in the acceptance ledger and prior change record.

## Result

`docs/knowledgebase.md` remains the correct comprehensive authoritative Vehicle Rental business knowledge base for this exact source corpus, and `docs/vehicle-rental/TODO.md` remains closed. No business rule or runtime implementation change is warranted by the supplied evidence.

This append-only change record documents the 2026-09-29 revalidation so future agents do not repeat the same audit or introduce speculative changes.
