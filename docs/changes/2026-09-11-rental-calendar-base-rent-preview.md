# Rental actual-calendar base-rent preview — 2026-09-11

Baseline: `41dbdef2dc69c67ffbf5ba323b48b726566f3843`, verified latest `worktree-0.0.8` with a clean checkout before work.

The user authorized independent documented decisions where project evidence cannot be extended. Implement a fresh, named actual-calendar estimation policy rather than embedding a guessed fixed-month divisor. Use agreement-start anniversary cycles, actual cycle day counts and inclusive civil dates. Recompute anniversaries from the original anchor so a short February does not permanently move a January 31 cycle. Use cumulative six-decimal allocations so adjacent partial periods reconcile exactly to one full cycle. Daily estimates use the recorded daily rate and included date count.

The Rental-owned service resolves the authorized agreement and its current version, rate and currency. Unknown base rates fail validation; zero remains valid. The client cannot set the calculation rate or monetary result. The API requires an explicit policy, valid covered dates and expected version. A named interactive resource limit bounds unusually large previews without limiting agreement duration.

Add the authenticated `base-rent-preview` action for both agreement kinds and a collapsed agreement-review form with explanatory scope, dates, breakdown and errors. Clear obsolete results after date edits and abort requests when the review changes. The returned agreement revision identifies the snapshot read; this read-only result creates no consumption, amendment or Invoice and cannot authorize a later financial write.

Relationship/ownership review: no new tables, relationships or inverse references are needed. Customer and Owner Agreement terms remain independent. Rental owns the estimate; Core owns exact decimal primitives. Do not move Tax, Invoice, Payment or Finance responsibilities into this service. No removed Rental code used.

Update `docs/knowledgebase.md`, add `docs/vehicle-rental/base-rent.md`, and record the delivered scope in the TODO. The policy is a documented new estimation convention, not an asserted historical TACGL formula. It does not resolve or implement other commercial components, source consumption, financial handoffs or production acceptance.

Verification:

- Full PHP/SQLite suite: **746 tests, 8,241 assertions**.
- Full frontend suite: **83 files, 307 tests**.
- Pint, ESLint, TypeScript, production Vite build, whitespace and local documentation links checked.
- Fresh migrations and seeders succeeded on a newly created isolated SQLite database.
- New backend cases cover leap years, short-month anchor recovery, partial and complete cycles, partition reconciliation, daily precedent arithmetic, zero/unknown rates, invalid dates and policies, coverage, stale revisions, customer/owner independence and no financial writes.
- Real-login API coverage verifies authorization, tenant isolation and validation; frontend coverage verifies period/revision forwarding, breakdown, errors, zero distinction and stale-result cancellation.

Real MySQL contention, production upgrade and browser/UAT remain separate checks not executed here. The protected backup's previously unsuccessful exact-candidate investigation supplies no new policy evidence and did not block this work. No paid tools, GitHub Actions or deployment.
