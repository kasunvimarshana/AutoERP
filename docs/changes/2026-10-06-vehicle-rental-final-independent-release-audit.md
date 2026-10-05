# Vehicle Rental final independent source and release audit — 2026-10-06

## Scope

Perform the requested final end-to-end independent review of the fresh Vehicle Rental implementation using the latest `worktree-0.0.8` source, the reconciled TACGL/video evidence, the closed acceptance ledger, architecture rules and current authoritative external corroboration.

Starting authoritative head: `aa2c0180c5c47a293e8a4a499eab9f885575bc72`.

No old/removed Rental implementation was restored, copied, cherry-picked or used as an implementation dependency.

## Findings

- The current Rental module is the fresh rebuild and its acceptance ledger has zero open checklist items.
- No new evidence-backed operational, commercial, financial, deposit, proration, mileage, replacement, downtime, accounting, validation, concurrency, migration, relationship or UI requirement was found missing from the current implementation/policy set.
- Core Rental services/models show no unresolved TODO/FIXME/HACK marker in the audited paths.
- Relationship ownership remains directional and maintainable: agreement successor, vehicle-use replacement and running-chart correction are predecessor links; history is append-only; canonical Customer/Supplier/Vehicle/HR/Invoice/Payment/Tax/Finance ownership is not duplicated in Rental.
- No production code change is justified merely to create a delta.

## External corroboration

Current Sri Lanka IRD material was checked as legal/tax context, not as a replacement for TACGL. It reinforces keeping VAT/WHT/AIT rates, thresholds and effective dates in the Tax owner module rather than hardcoding them in Rental.

IFRS 15 refund/consideration guidance was also checked as accounting context. It supports retaining refundable/unapplied customer money as a liability until the entity is entitled to it, consistent with the existing Payment/Finance-owned deposit design.

No external source was used to invent TACGL-specific replacement charging, downtime deductions, damage priority, deposit application priority, mileage pooling exceptions or miscellaneous tariffs.

## Protected backup investigation

Free local tooling re-inspected the accessible TACGL corpus:

- `tacdata/password.DBF` is readable and exposes historical application password fields;
- the nested `DATABACKUP/!   CTACGLDATABACKUP202503271759.rar` has 86 encrypted entries and no archive comment;
- no brute force, dictionary attack or arbitrary password guessing was used;
- the available archive backend reports RAR encryption support unavailable, so source-derived historical application credentials could not be validated against the nested archive here.

The inaccessible backup remains non-evidence and does not block the implemented business flow.

## Verification boundary

The latest application-code correction head is `07735be7f253cbcf55de0598cc19f477d41628c8`. The prior dependency-backed run identified concrete failures, and that commit contains their source corrections.

A fresh full dependency-backed post-correction run cannot be executed in this environment because shell DNS cannot resolve GitHub and no complete Composer/npm dependency tree is available. GitHub Actions and paid runners remain excluded by instruction.

Accordingly, this record does **not** fabricate a green full-suite result. The limitation is release evidence, not an identified unfinished Rental product requirement.

## Change decision

Documentation only:

- add this final independent audit record;
- update the canonical knowledge base with the result, standards corroboration and backup-recovery boundary.

No runtime/schema/API/relationship/permission/calculation/UI file is changed because no evidence-backed defect was found.
