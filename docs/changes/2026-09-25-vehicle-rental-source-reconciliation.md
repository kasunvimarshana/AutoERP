# Vehicle Rental source reconciliation — 2026-09-25

## Scope

Reconciled the canonical Vehicle Rental business knowledge base against the newly supplied TACGL archives, duplicate engineering instruction files, the four authoritative Vehicle Rental videos, and the latest `worktree-0.0.8` implementation baseline.

## Source verification

- `TACGL.zip` SHA-256 remains `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`.
- `TACGL(20260925-035203).zip` has different archive bytes but the same 452 inner business files after removing the optional `TACGL/` wrapper; every normalized path and file SHA-256 matches the canonical archive.
- `RULES.md`, `RULES(20260925-033954).md`, `AGENTS.md`, and `AGENTS(20260925-033955).md` are byte-identical with SHA-256 `46fc617dd24e79b0bb19c20b2f8890c180b0ba8611b32f9c4e462f050aba7c77`.
- Video hashes remain unchanged and therefore no new conflicting workflow evidence was introduced.
- Latest authoritative branch head inspected before this change: `e8576e3f7ea0923852cc5ff0487994a9d992e45e`.

## Documentation change

Rewrote `docs/knowledgebase.md` as a concise self-contained canonical domain reference that preserves:

- TACGL as primary business source and conflict tie-breaker;
- supplied videos as practical workflow evidence;
- separate customer and owner agreements;
- Running Chart as shared physical evidence;
- independent customer billing and owner settlement;
- simple agreement-first vehicle-selection UX;
- explicit module ownership boundaries;
- immutable/versioned financial and operational history;
- source-consumption, tenant and concurrency invariants;
- explicit unresolved policy gates instead of guessed defaults.

## Runtime decision

No Vehicle Rental production-code change was made in this reconciliation pass. The current fresh module already contains the confirmed operational and delivered commercial foundations, while remaining financially material TODO items depend on business policies that TACGL/videos do not uniquely determine. Implementing those items without new evidence would violate the repository rule against guessing and hardcoded/legacy workarounds.

## Verification

- Source archive normalized-content comparison: passed, 452/452 inner files identical.
- Duplicate RULES/AGENTS hash comparison: passed.
- GitHub branch/source reconciliation: passed.
- Runtime files changed: none.
