# Integrity retention and sequence generation correction

Date: 2026-07-11

## Problem

The project-wide audit confirmed two independent integrity defects.

First, status-history and rental-deposit evidence migrations used cascading parent foreign keys. A hard delete, force delete, maintenance script, or direct database operation could therefore erase historical and financial evidence together with the current aggregate.

Second, first-use sequence generation used a query-then-create flow. Concurrent requests could both observe a missing sequence and race on the unique scope. The service also converted nearly every unexpected failure into `SEQUENCE_INVALID_VALUE`, so infrastructure and programming failures were presented as user validation errors.

## Correction

Historical evidence now uses restrictive parent deletion semantics for:

- customer status history;
- supplier status history;
- employee status history;
- vehicle status history;
- vehicle-service job status history;
- vehicle-finance agreement and installment history;
- rental deposit requirements;
- rental deposit links.

A dynamic architecture test discovers every `*_status_histories_table.php` migration and rejects destructive cascade actions. Rental deposit evidence has an explicit retention contract as well.

Sequence generation now:

- inserts a missing scoped sequence with `insertOrIgnore` inside the existing transaction;
- reloads and locks the authoritative row whether this request or a concurrent request created it;
- retains optimistic version protection for number consumption;
- distinguishes invalid input, concurrency conflicts, and unexpected internal failures;
- returns a safe generic message for internal failures;
- maps typed generation failures to the appropriate HTTP status;
- uses `SequenceErrorCode` constants instead of duplicated raw error-code literals.

## Verification

- Reviewed the exact diff against `worktree-0.0.8`.
- Added `HistoricalRetentionMigrationTest` with dynamic migration discovery.
- Added `GenerateSequenceNumberServiceTest` covering concurrent first-use reuse, optimistic conflicts, and internal-error classification.
- PHP syntax validation was run for every changed/new PHP file available in the correction batch.
- No GitHub Actions or paid tools were used.

## Scope

This change intentionally fixes only the confirmed retention and sequence-generation root causes. It does not claim that unrelated audit findings are closed.
