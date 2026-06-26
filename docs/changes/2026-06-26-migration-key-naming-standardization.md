# Migration key naming standardization

Date: 2026-06-26

## Scope

Normalized key and constraint names across all application and framework migrations without changing table columns, relationships, delete actions, business rules, or migration ordering.

## Convention

```text
<table-or-clear-alias>_<business-purpose>_<suffix>
```

Suffixes:

- `_fk` — foreign key
- `_uk` — unique key
- `_ix` — normal index
- `_pk` — explicit custom/composite primary key
- `_ck` — check constraint

The project-level maximum is 60 characters, below MySQL's 64-character identifier limit.

## Changes

- Added explicit names to all convention-based `foreignId()->constrained()` relationships.
- Replaced `_idx` index suffixes with `_ix`.
- Added names to previously unnamed fluent unique, index, and custom primary definitions.
- Corrected one unique key that incorrectly used an `_idx` suffix.
- Replaced truncated hash-based foreign-key names with concise relationship-oriented names.
- Preserved existing explicit semantic names and dynamic helper prefixes where they already followed the convention.
- Added no patch or compatibility migrations.

## Verification

- 248 migration files and 251 created tables inspected.
- 2,060 literal named keys and 25 explicit dynamic key names validated.
- 1,039 foreign keys, 451 unique keys, 564 indexes, and 6 explicit custom primary keys validated.
- No unnamed constrained foreign keys remain.
- No legacy `_idx`, `_index`, `_foreign`, `_unique`, or `_primary` suffix remains in key declarations.
- No key or constraint name exceeds 60 characters.
- No schema-wide foreign-key name collision remains.
- No table-local index-name collision remains.
- All 248 migration files pass PHP syntax validation.

A real `migrate:fresh --seed` execution still requires the Composer-installed runtime and a disposable MySQL database.
