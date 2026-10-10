# Rental incident migrations — one-table-per-file correction (2026-10-10)

## Context

Before preparing PR #123 for integration, re-read the newly supplied identical RULES/AGENTS instructions and the implementation diff. Section on database schema migrations requires **one table per migration file**, explicit foreign keys, portable Laravel APIs and no unrelated migration rewrites.

## Change

The new, previously unmerged incident migration creating two related tables was replaced by exactly two sequenced, single-table migrations:

1. `2026_10_10_000001_create_vehicle_rental_incidents_table.php` creates only `vehicle_rental_incidents`.
2. `2026_10_10_000002_create_vehicle_rental_incident_events_table.php` creates only `vehicle_rental_incident_events`.

The second migration references the first and rolls back before the first on down migration order. All incident business field types, role/tenant composite FKs, indexes, immutability semantics and ownership remain unchanged. The original two-table migration is removed from the **unmerged PR branch**, not rewritten in already-deployed migration history.

## Evidence and limits

Verified GitHub file contents, explicit migration creation/drop counts, and PR diff. A migration parse mistake discovered during the split was corrected prior to this record. MySQL foreign-key constraint creation, fresh install, rollback, InnoDB concurrency, PHP lint, full Laravel/Vitest suites and live deployment remain unexecuted. This change does **not** authorize a production release or imply complete financial adjustment support.
