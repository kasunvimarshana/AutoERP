# Finance posting profile rule validity cleanup

## Context

Finance posting profile rules map module-owned semantic posting keys to Finance-owned account roles. The previous save flow deleted all profile rules and recreated the submitted set, and account resolution selected the current rule by `line_key` only.

That was not a clean accounting foundation because backdated postings could resolve through today's profile rule mapping instead of the rule version valid for the posting date.

## Changes

- Added `effective_from`, `effective_to`, and `is_active` to `finance_posting_profile_rules`.
- Changed the profile rule uniqueness boundary from profile + line key to profile + line key + effective-from date.
- Kept account resolution semantic: posting lines still resolve through `profileKey -> posting profile rule -> account role -> effective account assignment`.
- Changed posting profile save logic to upsert rule versions instead of deleting/recreating all rules.
- Added overlap validation so active rule versions for the same profile and line key cannot cover the same date.
- Changed account resolution to select the active rule version valid on `PostingContext::$postingDate`.
- Exposed rule validity fields through API resources and frontend types.

## Verification target

Run the standard backend and frontend suites:

```bash
php artisan test
npm run typecheck -- --pretty false
npm run lint
npm run build
npm run test
```
