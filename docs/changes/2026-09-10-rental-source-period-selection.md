# Period-aware owner source selection — 2026-09-10

Baseline: `8b227aa3c7e618015ebfa00cc93c083047b374fb`, latest verified `worktree-0.0.8`.

The owner-source selector previously listed active agreements without checking whether they covered the requested hire. Add a Rental-owned source-query service and require the planned start and explicit nullable planned end. Return only sources covering the selected physical vehicle, current tenant/organization and complete planned period. Preserve the existing inclusive agreement-date and half-open use-timestamp semantics, including a return exactly at midnight and open-ended source requirements. This mirrors the implemented plan validator; it does not establish new financial policy.

Share planned-period parsing with the plan command through `OperationalTime::plannedPeriod`. Move source-query responsibility out of the controller. Keep final plan/handover coverage and shared Vehicle availability checks transactional and unchanged. A lookup result is advisory, not a reservation.

The assignment/replacement form requests sources using its current dates, places period entry before source selection, and clears the selection when vehicle/start/end changes. Remounting the lookup on those changes prevents obsolete search results from retaining the old context. Open-ended query input is explicit and normalized to null by the request middleware. No contextual prices are exposed.

Relationship review: no schema, foreign-key, inverse relationship or module dependency changes. Existing Owner Agreement -> Vehicle and use -> Owner Agreement links are sufficient. Rental owns this lookup; Vehicle continues to own shared availability. No old Rental code reused.

Additional authorized backup recovery: search supplied/extracted `.txt`, `.md`, `.ini`, `.cfg`, `.bat` and `.prg` files for password/archive-key command clues. No matching file or new candidate was found. Do not retry the five previously unsuccessful exact DBF candidates or publish credentials. No brute force or guessed variants. The encrypted backup remains unavailable; implementation continued independently.

Verification:

- Full PHP 8.3/SQLite suite: **718 tests, 8,126 assertions passed**, including fresh migration/architecture checks.
- Frontend: **82 files, 303 tests passed**.
- PHP Pint, ESLint, TypeScript, production build and whitespace checks passed.
- Source tests cover Draft/Active/Closed filtering, exact midnight end, insufficient finite coverage, open ends, invalid/missing periods and vehicle/tenant/branch scope. Real-login acceptance uses the new source request contract. UI tests use the real lookup component and verify period forwarding and stale selection clearing.

Update the knowledge base, operational API contract and TODO. Keep vehicle-availability prefiltering distinct from completed source-coverage filtering. The complete commercial module, unresolved policy gates, full audiovisual review and MySQL/browser/production acceptance remain unfinished. Free local tools only; no GitHub Actions or production deployment.
