# Vehicle Rental knowledge-base reconciliation — 2026-10-01

## Scope

Refresh the canonical Vehicle Rental business knowledge base against the newly supplied TACGL archive set, all four authoritative videos, root `RULES.md` / `AGENTS.md`, and the current authoritative `worktree-0.0.8` branch.

This task is evidence/documentation reconciliation only unless the audit proves a runtime defect or missing business capability.

## Evidence reviewed

- `TACGL.zip`
  - SHA-256: `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`
- `TACGL(20261001-005122).zip`
  - SHA-256: `79c240494943437978754169c3360bb7c6e35d911ef8263c4b2d6b6246384d77`
- `TACGL.rar`
  - SHA-256: `0001a91ab4dfdf31d60f669b30f1ccc2da475d7841b2ceea505ef97eff5bedbf`
- `1.mp4`
  - 40:50
  - SHA-256: `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf`
- `Recording 2026-06-21 132314.mp4`
  - 41:58
  - SHA-256: `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa`
- `2.mp4`
  - 21:14
  - SHA-256: `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f`
- `ScreenVideo_03-04-2026_18-02-52.mp4`
  - 12:24
  - SHA-256: `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9`
- root `RULES.md` / `AGENTS.md`
- `docs/vehicle-rental/TODO.md`
- current `docs/knowledgebase.md`
- authoritative branch head `9f492b5094039522c53b4e7509be296217b2d5b9`

## TACGL reconciliation

Both ZIP packages contain 452 non-directory business files.

After normalizing the canonical archive's outer `TACGL/` wrapper:

- path sets are identical;
- every per-file SHA-256 is identical;
- there are zero added, removed or changed business payload files.

Therefore `TACGL(20261001-005122).zip` adds fresh provenance metadata but no conflicting or additional business rule.

The extracted TACGL corpus continues to corroborate the existing domain model through Visual FoxPro data/report structures including vehicle, debtor/customer, creditor/supplier, invoice, debtor/creditor subledger, transaction and GL data.

## Video reconciliation

The four supplied videos remain the same evidence set already represented by the knowledge base.

The workshop-focused `ScreenVideo_03-04-2026_18-02-52.mp4` remains supporting evidence for vehicle availability/maintenance boundaries only and is not treated as a Rental tariff source.

## Implementation reconciliation

The previous knowledge-base review head was `8b74ff48a1dbf2a07220f5edcb36dabc3086f980`.

The current authoritative `worktree-0.0.8` head is `9f492b5094039522c53b4e7509be296217b2d5b9`.

The current branch is 50 commits ahead of the prior review point, but the compare contains no changes under `app/Modules/VehicleRental`.

Later work is concentrated in other owner modules, including Selling, Inventory, Invoice/Payment, Reporting, Vehicle Service, Expense and supporting Core/Vehicle/Customer/Supplier changes.

Result: no evidence-backed Vehicle Rental runtime change is required in this reconciliation pass.

## Documentation changes

Updated `docs/knowledgebase.md` to:

- set refresh date to 2026-10-01;
- set current authoritative branch head;
- register `TACGL(20261001-005122).zip` and its hash;
- state verified normalized payload equivalence with canonical `TACGL.zip`;
- preserve the four-video evidence hashes/durations;
- make TACGL DBF/accounting corroboration explicit;
- restate business vs production-policy boundaries;
- retain customer/owner independence and Running Chart invariants;
- retain owner-module boundaries and simple operator workflow;
- retain the ambiguity register for unsupported monetary rules;
- add current implementation reconciliation and AI-agent decision rules.

## Runtime/code impact

None.

No Vehicle Rental PHP/TypeScript/migration/API/runtime file was modified.

No legacy Rental code was restored, copied or reused.

No compatibility workaround, magic value or guessed financial rule was introduced.

## Verification

Documentation/source reconciliation performed with:

- SHA-256 checks of supplied artifacts;
- normalized ZIP path/hash manifest comparison;
- direct TACGL DBF schema inspection;
- video metadata/frame sampling;
- GitHub branch/head verification;
- commit comparison from prior Rental knowledge-base review head to current authoritative head;
- current Vehicle Rental module and acceptance-ledger inspection.

No claim is made that application test suites were rerun for this documentation-only change.
