# Customer security deposits through Payment — 2026-09-20

Baseline: `f0ec8e6fff0eee82351367b06673ec604f7865dc` on `worktree-0.0.8`. The preceding mileage tree was independently compared with GitHub and published without force or CI execution.

Add controlled deposit receipt/read APIs from an active customer agreement with an explicit positive requirement. Derive party/currency/source from the scoped agreement; preserve agreement reference/revision/requirement in Payment metadata. Require separate Rental view and Payment create/view permissions plus both module entitlements. Share Payment-owned line validation and frontend instrument payload preparation rather than duplicating financial input rules in Rental.

Serialize receipt admission under the agreement lock and require the current agreement revision. Existing Payment idempotency provides exact retries and payload conflict checks. Check capacity after creation/replay inside the enclosing transaction so retries at a fully received requirement still succeed. Count surviving drafts and posted receipts less active refunds; application does not free collection capacity. Roll back all new effects on excess. Partial receipts are supported. Void/reversal releases capacity while retaining history; later refund reversal preserves restored liability even if it creates an excess requiring disposition.

Payment owns deposit liability posting, explicit invoice application, refunds and reversals. Add a distinct source enum for the fresh agreement, recognize it in the existing semantic posting policy, validate deposit source/type/direction at Payment creation, and classify deposit balances by the supported enum instead of arbitrary substring matching. Preserve existing Payment source meanings. No old Rental implementation was restored or referenced.

Relationship review: the immutable customer agreement already owns the requirement, and Payment already owns directed source identities and the full money lifecycle. Reuse its scoped source query and existing source index. Do not add a competing Rental cash ledger, inverse FK or new table. No schema migration is required. Receipt/Invoice/Payment source identities remain in their owning modules.

Add a guided deposit panel under customer agreement review with configured methods, explicit date/exchange rate, receipt capacity, document/posting states, balances and payment links. Keep keys for uncertain retries and retire them on confirmed success. Remove stale UI wording saying billing is disabled. Document the chosen policy, applicable primary-source distinction of refundable security, correction order and production configuration in `docs/vehicle-rental/deposits.md` and knowledge-base section 41. Close only the completed deposit TODO entries.

Rechecked the newly surfaced TACGL(10).zip against TACGL(9).zip: identical bytes and inventory. SHA-256 `79c240494943437978754169c3360bb7c6e35d911ef8263c4b2d6b6246384d77`. No new password candidate was present. Read both newer 2026-09-17 instruction files; ownership/concurrency/audit-history requirements are unchanged. The protected backup did not block implementation.

Verification:

- Full backend SQLite suite: **784 tests, 8,672 assertions**.
- Full frontend suite: **88 files, 325 tests**.
- Pint, ESLint, TypeScript, Vite production build and whitespace checks passed.
- Real authenticated APIs cover receipt/retry/capacity rollback; unknown/zero/draft requirement rejection; stale version; source spoof rejection; authorization, entitlement and tenant isolation; partial receipts; void/replacement; approval/posting; Invoice application; partial refund; stale refund; refund reversal followed by original receipt reversal.
- The money lifecycle uses actual Payment/Invoice/Finance services and test-only semantic accounts. No financial service is mocked in that journey. Frontend coverage includes uncertain network retry identity and prevention of excess/zero collection.
- Isolated fresh SQLite migration and seeding passed; deposit implementation makes no schema changes. The schema matches the published mileage baseline. Real InnoDB contention, production-data migration and human UAT are not claimed.

No paid tools, GitHub Actions, force push or deployment.
