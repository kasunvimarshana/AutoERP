# Vehicle Rental successor-lineage schema integrity

Date: 2026-09-25

## Finding

The fresh Vehicle Rental service/model/API already implemented and documented one-way agreement revision lineage through `supersedes_agreement_id`, and `AgreementSuccessorTest` exercised that workflow. The customer and owner agreement migrations, however, never created the column. A truly fresh schema therefore could not persist a successor draft even though the application layer expected the field.

This was a schema ownership defect in Vehicle Rental, not a missing business decision and not a reason to restore legacy Rental code.

## Correction

- Added nullable `supersedes_agreement_id` to both fresh Rental agreement tables through an additive Vehicle Rental migration.
- Added a scoped self foreign key over `(supersedes_agreement_id, tenant_id, organization_unit_id)` so lineage cannot reference another tenant or organization unit.
- Added the scoped parent identity unique key required by the composite self foreign key.
- Added a unique constraint on `supersedes_agreement_id`, enforcing at most one direct successor per predecessor even under concurrent requests.
- Kept the relationship intentionally one-way; no inverse `successor_id` column was introduced.
- Protected predecessor lineage from mutation after a successor draft has been created.
- Added a focused regression for competing direct successors. Existing `AgreementSuccessorTest` already covers successful customer/owner successor creation and activation on a fresh `RefreshDatabase` schema.

## Relationship rationale

The relation is not new business scope. It already belongs to Agreement revision semantics: a successor is a future commercial revision of exactly one predecessor. Persisting the existing direction is the minimal normalized design. A redundant inverse pointer would create synchronization risk and is therefore intentionally absent.

## Verification

Actually executed in this environment:

- PHP 8.4.23 syntax validation for the new migration, modified Agreement model, modified immutability test, and new successor-integrity test.
- Static comparison against the current `worktree-0.0.8` agreement migrations, model, service, resource, frontend type contract, existing successor tests, and the repository's established scoped self-FK pattern.
- Verified the default PHPUnit configuration uses SQLite `:memory:`; Laravel 12 is the declared framework version. The migration uses portable Schema Builder primitives rather than driver-specific SQL.
- Re-probed direct Git access; the execution container still cannot resolve `github.com`, so Composer/PHPUnit/frontend/MySQL suites cannot be truthfully claimed as re-executed here.

GitHub Actions were not used.

## Protected backup

No new password evidence was discovered. The prior legitimate free-tool inspection remains authoritative: the nested RAR has 86 encrypted entries, no archive comment, and accessible TACGL text/configuration contains no explicit backup credential. No brute force, dictionary attack, arbitrary mutation, or invented password was used.
