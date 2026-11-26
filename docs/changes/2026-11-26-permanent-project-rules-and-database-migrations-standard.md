# 2026-11-26 — Permanent Project Rules, Laravel Migration Standards, and Markdown Governance

## Summary

- **Permanent Project Rules Documentation (`PROJECT_RULES.md`, `docs/PROJECT_RULES.md`)**:
  - Established a dedicated, permanent Markdown rules file governing development and AI agent operations across AutoERP.
  - Signed under the top-level title with authoritative credentials:
    - **Author:** Kasun Vimarshana
    - **Email:** `kasunvmail@gmail.com`
    - **Phone:** `+94713525253`
- **Data Correctness & Careful Design Axiom**:
  - Formalized that data correctness is mandatory and the top priority across the entire system.
  - Enforced that developers and agents must think carefully before designing or implementing anything that touches persistent data, preserving ledger consistency and historical audit trails.
- **Laravel Database Migration Best Practices (MUST and MUST NOT)**:
  - Formulated all 7 mandatory migration engineering standards as explicit MUST and MUST NOT directives:
    1. **Migration Immutability:** Never edit committed/deployed migrations; create new migrations to alter schema.
    2. **Atomic Migrations:** Exactly one logical change per migration with a short, descriptive name.
    3. **Reversible Rollbacks:** Mandatory `down()` method strictly reversing `up()` for single-command Artisan rollback.
    4. **Driver Independence:** Exclusive use of Laravel Schema Builder and Blueprint fluent methods over raw SQL.
    5. **Database-Level Data Integrity:** Mandatory database-level foreign keys with `constrained()`, unique constraints, and explicit `nullable()` modifiers rather than relying solely on application-level validation.
    6. **Schema Consolidation:** Periodic consolidation of mature migration suites via `php artisan schema:dump`.
    7. **Separation of Schema and Seeding:** Absolute separation of migrations from data inserts; use idempotent Seeders with `updateOrCreate()` or `firstOrCreate()`.
- **Markdown Portability & Storage Standards**:
  - Enforced CommonMark / GFM compliance, unambiguous syntax, and markdownlint formatting invariants (ATX headings, single H1, language tags on fenced blocks, blank lines around block elements, zero trailing whitespace, single trailing newline).
  - Defined explicit documentation boundaries: durable project rules reside in `PROJECT_RULES.md`, while temporary notes, scratchpads, and draft plans reside in `docs/notes/`.
- **Project-Wide Audit & Code Signing Protocol**:
  - Codified the protocol for deep, end-to-end repository audits and standardized author signing across all relevant code and documentation without altering business logic or runtime behavior.

## Files Added
- `PROJECT_RULES.md`
- `docs/PROJECT_RULES.md`
- `docs/notes/README.md`
- `docs/changes/2026-11-26-permanent-project-rules-and-database-migrations-standard.md`

## Verification
- CommonMark and markdownlint programmatic verification on `PROJECT_RULES.md` and `docs/PROJECT_RULES.md`:
  - 1 top-level H1 heading (MD025 / MD041).
  - ATX heading format with single-step hierarchy increments (MD001 / MD003).
  - Blank lines before and after headings, fenced code blocks, and list items (MD022 / MD031 / MD032).
  - Explicit language tags on all fenced code blocks (`bash`, `php`) (MD040).
  - 0 trailing spaces across all lines (MD009).
  - 0 hard tabs (MD010).
  - 0 consecutive blank lines (MD012).
  - 0 bare unformatted URLs (MD034).
  - Exact single trailing newline at end of file (MD047).
