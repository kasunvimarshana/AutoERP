# 2026-11-26 — Durable Project Rules and Markdown Governance Standard

## Summary

- **Durable Project Rules (`DURABLE_RULES.md`, `docs/DURABLE_RULES.md`)**:
  - Authored the authoritative, permanent reference document establishing project-wide rules for developers and AI agents.
  - Formally signed under the H1 heading with official author credentials:
    - **Author:** Kasun Vimarshana
    - **Email:** `<kasunvmail@gmail.com>`
    - **Phone:** `+94713525253`
- **Rule Precedence and Governance**:
  - Established RFC 2119 keyword meanings (**MUST**, **MUST NOT**, **SHOULD**).
  - Explicit conflict resolution precedence: data integrity first, followed by unchanged application behavior, then all other requirements.
- **Data Integrity (Prime Directive)**:
  - Enforced schema-level constraints over application-level assumptions.
  - Mandatory boundary validation via Form Requests.
  - Multi-step writes enclosed in `DB::transaction()`.
  - Prohibited unapproved dropping or narrowing of columns and tables.
  - Enforced `decimal` or integer minor units for monetary figures (strictly prohibiting `float`).
  - Production guard in `AppServiceProvider::boot()` via `DB::prohibitDestructiveCommands($this->app->isProduction())` and `RollbackCommand::prohibit(false)`.
- **Database Migrations (Laravel)**:
  - Codified the 7 mandatory Laravel migration rules as explicit MUST/MUST NOT directives:
    1. Immutability of pushed migrations (restating all modifiers when altering with `->change()`).
    2. Atomic, focused migrations (single table/index per file with descriptive name).
    3. Reversible `down()` methods reversing `up()` for clean single-command rollbacks.
    4. Exclusive use of Laravel Schema Builder and Blueprint fluent methods over raw SQL.
    5. Database schema integrity via `constrained()`, `unique()`, and explicit `nullable()`.
    6. Migration squashing in mature projects via `php artisan schema:dump --prune`.
    7. Clean separation of schema migrations from data seeding using idempotent Seeders (`updateOrCreate()`, `firstOrCreate()`).
    8. Standardized migration template containing author docblock.
- **Authorship and Code Signature Protocol**:
  - Detailed cross-stack signature conventions across 10 file categories (`.php`, `.blade.php`, `.js`/`.ts`, `.css`/`.scss`, `.html`, `.sh`/`.yml`/`Dockerfile`, `.sql`, `.md`, `composer.json`/`package.json`, other JSON).
  - Invariant: code signatures are comment-only / metadata-only and MUST NOT alter runtime behavior or logic.
  - Clear exclusion list: `vendor/`, `node_modules/`, `storage/`, `bootstrap/cache/`, build output, lock files, generated files, binaries, `.env`, and pushed migrations.
- **Project Audit Protocol**:
  - 4-step procedure: full inspection, baseline testing, findings logged in `docs/notes/YYYY-MM-DD-audit.md`, and proof of zero regression.
- **Markdown Standards and Linter Configuration**:
  - Standardized on CommonMark and GFM.
  - Created `.markdownlint-cli2.jsonc` at repository root.
- **Separation of Durable Rules vs. Temporary Notes**:
  - Permanent rules reside strictly in `DURABLE_RULES.md`.
  - Ephemeral notes, TODOs, and work-in-progress logs reside in `docs/notes/YYYY-MM-DD-topic.md`.
- **Pre-Merge Checklist**:
  - Actionable 8-point verification checklist for all pull requests and agent completions.

## Files Created / Modified

- `DURABLE_RULES.md` (Created)
- `docs/DURABLE_RULES.md` (Created)
- `PROJECT_RULES.md` (Updated)
- `docs/PROJECT_RULES.md` (Updated)
- `.markdownlint-cli2.jsonc` (Created)
- `docs/changes/2026-11-26-durable-project-rules-markdown-standards.md` (Created)

## Verification Results

- `markdownlint-cli2 --no-globs DURABLE_RULES.md docs/DURABLE_RULES.md PROJECT_RULES.md docs/PROJECT_RULES.md`:
  - 4 files scanned.
  - 0 issues found (100% compliant).
- Structural checks:
  - 1 H1 per file.
  - Explicit language tags on all fenced code blocks (`php`, `json`).
  - Blank lines surrounding headings, lists, tables, and code blocks.
  - Zero trailing whitespace.
  - Valid CommonMark autolinks and relative path links.
  - Single trailing newline.
- Runtime safety:
  - 0 application source files modified.
  - 0 database records or migrations altered.
