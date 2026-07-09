# Finance Posting Line Contract Cleanup

## Summary

- Removed direct account selector fields from the Finance `PostingLine` DTO.
- Finance posting account resolution now relies on semantic `profileKey` mappings only.
- Updated Finance revaluation and Fast Purchase posting producers to use named line labels and semantic profile keys without dummy account-code arguments.
- Added a boundary test that ensures `PostingLine` does not expose `accountCode` or `account` constructor/property surfaces.

## Root cause

Finance runtime already rejected source postings that selected accounts by code, but `PostingLine` still exposed legacy-shaped `accountCode` / `account` fields. That made the DTO contract look like callers could choose Finance accounts directly even though the supported design is profile key → account role → scoped account assignment → account.

## Design notes

- `PostingLine` now carries posting facts and the semantic `profileKey` only.
- `PostingProfileService` resolves accounts through the posting profile rule role and scoped account assignment.
- Business modules continue to pass semantic posting keys, not Finance account codes or IDs.
- No Finance account assignment, posting profile schema, journal, ledger, or frontend behavior was changed.

## Verification

- Source readback should confirm `PostingLine` has no `accountCode` or `account` property/constructor parameter.
- Source readback should confirm `PostingProfileService::resolveAccount` requires a semantic posting profile mapping key.
- Full local `php artisan test`, frontend typecheck, lint, build, and Vitest should be run before merging.
