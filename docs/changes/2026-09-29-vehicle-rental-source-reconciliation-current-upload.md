# Vehicle Rental current-upload source reconciliation

Date: 2026-09-29
Target branch: `worktree-0.0.8`
Reviewed target head at audit start: `8b74ff48a1dbf2a07220f5edcb36dabc3086f980`

## Scope

Revalidate the exact TACGL archives, `RULES.md` / `AGENTS.md`, and all four Vehicle Rental source videos supplied in the current conversation, reconcile them with the latest authoritative `worktree-0.0.8` implementation, and refresh the canonical `docs/knowledgebase.md` without inventing runtime work.

## Source checks executed

- `TACGL.zip` SHA-256: `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`.
- `TACGL(20260929-164333).zip` SHA-256: `79c240494943437978754169c3360bb7c6e35d911ef8263c4b2d6b6246384d77`.
- `TACGL.rar` SHA-256: `0001a91ab4dfdf31d60f669b30f1ccc2da475d7841b2ceea505ef97eff5bedbf`.
- Both ZIPs contain 452 non-directory business files.
- After normalizing the canonical ZIP's outer `TACGL/` wrapper, all 452 relative paths and every per-file SHA-256 match exactly.
- Four video SHA-256 values were recalculated and match the canonical evidence register.
- Video durations were revalidated with `ffprobe`: approximately 40:50, 41:58, 12:24 and 21:14.
- TACGL DBF/report inventory was re-examined for vehicle, customer/debtor, owner/creditor, transaction, receipt/payment, bank, vehicle-service/job and reporting evidence.

## Implementation reconciliation

The latest authoritative branch contains the fresh `app/Modules/VehicleRental` implementation with dedicated agreement, vehicle-use/custody, Running Chart, mileage, billing, authorization, deposit-linkage and availability responsibilities.

The release record states that the validated runtime baseline is `2c80584446536fa8b1ebfec4c05adbd129706f45`, the pre-release completion-audit head is `890d14bbacaa39b0e271faa3fa83b08ee596b2e6`, and later release-candidate commits are documentation-only. The closed `docs/vehicle-rental/TODO.md` acceptance ledger records the fresh implementation as completed.

No evidence-backed unfinished runtime requirement, new tariff, tax percentage, withholding threshold, proration rule, replacement surcharge, downtime deduction, garage-KM billing rule, deposit forfeiture rule or extra approval stage was discovered during this revalidation.

Accordingly, no runtime code/schema/API/permission/frontend change is justified by this audit. Creating a code delta only to satisfy the request would violate the project's evidence-over-guessing and minimal-change rules.

## Documentation changes

`docs/knowledgebase.md` was refreshed to:

1. record the exact current dated upload name `TACGL(20260929-164333).zip`;
2. record current source hashes and 452-file byte-equivalence;
3. distinguish authoritative branch head from the validated runtime baseline;
4. consolidate the canonical business model, states, workflows, calculations, validation rules, module ownership and UI contract;
5. retain explicit safe production decisions for historically ambiguous rules;
6. keep the implementation TODO as a closed acceptance ledger rather than reopening speculative scope;
7. state that no runtime change is warranted without a reproducible defect or new authoritative business evidence.

## Verification not rerun

This branch changes documentation only. The following executable gates were not rerun in this session:

- Laravel / SQLite suite;
- MySQL suite;
- frontend Vitest;
- TypeScript typecheck;
- ESLint;
- Vite production build;
- browser UAT.

The production release record already documents the last validated runtime gates. This documentation-only reconciliation does not modify the tested runtime tree.
