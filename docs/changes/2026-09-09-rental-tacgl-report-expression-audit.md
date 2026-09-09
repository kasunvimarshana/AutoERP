# TACGL Rental report-expression source audit — 2026-09-09

Baseline verified: `5095cdda9b9b9b6589dc582869a1352f51be4156`, `worktree-0.0.8`.

Read all 109 unencrypted FoxPro FRX/FRT report pairs in the mounted TACGL archive. Validate DBF record bounds and memo block bounds, skip deleted records, and extract text without executing the legacy application. Extraction returned 3,996 report records with 3,718 EXPR and 621 SUPEXPR text fields. Semantic review targeted Rental keywords and arithmetic/rounding expressions; it did not review every report execution path or video narration.

Add `docs/vehicle-rental/report-expression-audit.md` with R01–R06, one-based physical record references, selected source hashes, exact expressions and interpretation boundaries. Update the knowledge base and source-work TODO accordingly.

Findings distinguish invoice display arithmetic, purchase/material report rounding, an ambiguous date heading and a hired-vehicle report caption from actual Rental pricing policy. Do not infer a monthly divisor, allowance pool, replacement charge, driver entitlement, tax policy or enum from those presentation expressions. The missing financial policies remain unresolved; no financial implementation is claimed from this evidence.

Relationship/ownership review: no runtime, schema, relationships, module dependencies or ownership changes. No removed AutoERP Rental code was used. No legacy executable or protected backup was run. No passwords or source credentials were published.

Verification: extraction completed across every unencrypted report pair without boundary errors; selected report/memo SHA-256 fingerprints were recorded; documentation whitespace checks passed. This is documentation-only, so backend/frontend suites were not repeated. The unchanged implementation's prior verified baseline has 709 PHP/SQLite tests and 8,034 assertions passing. Real-engine, browser/UAT and production acceptance remain outstanding.

Free local Python standard-library tools only; no paid services, GitHub Actions or deployment. The complete Vehicle Rental module remains unfinished.
