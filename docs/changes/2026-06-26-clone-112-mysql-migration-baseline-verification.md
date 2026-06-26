# Clone 112 MySQL migration baseline verification

Date: 2026-06-26

## Why

The fresh MySQL migration baseline needed a complete structural review after prior foreign-key, timestamp, seeder, and naming corrections. The remaining migrations also had readability and history-stability issues: generated foreign keys in User, reusable schema helpers with dynamic constraint prefixes in HR, multiple tables in Laravel default migrations, and schema enum definitions coupled to mutable application classes.

## What changed

- Expanded every generated User actor foreign key explicitly, line by line, with meaningful `_fk` names.
- Removed HR migration `scope()` helpers and dynamic constraint prefixes; each tenant and organization-unit column/foreign key is now declared directly in its owning table migration.
- Split the default cache/jobs migrations so every migration creates and drops exactly one table.
- Removed application-class imports from migrations and froze enum choices directly in the historical schema definition.
- Preserved table structures, column semantics, foreign-key actions, indexes, business ownership, and migration ordering except for the required one-table-per-file split.
- Added no patch migrations, compatibility branches, driver checks, raw SQL, or unrelated application changes.

## Constraint convention

- Foreign key: `_fk`
- Unique key: `_uk`
- Normal index: `_ix`
- Explicit custom/composite primary key: `_pk`
- Check constraint: `_ck`

All key names are explicit lowercase snake case and limited to 60 characters, below MySQL's 64-character identifier limit.

## Verification completed

- 251 migration files create 251 unique tables.
- 4,283 columns, 1,266 indexes, and 1,072 foreign keys were statically validated.
- No duplicate migration basenames or duplicate table creation.
- No multi-table migrations or `Schema::table()` patch migrations.
- No loops, conditionals, schema helper methods, dynamic column/key names, driver branches, or raw SQL in migrations.
- No application-code imports or dynamic enum-value providers in migrations.
- Every foreign key/index/unique/custom-primary name is explicit and uses the standard suffix.
- No identifier exceeds 60 characters; no schema-wide duplicate foreign-key names.
- Foreign-key target tables, columns, types, indexes, migration order, self-reference order, and delete actions passed static validation.
- No composite `SET NULL` foreign key references a non-nullable child column.
- MySQL decimal, timestamp, index-count, and InnoDB key-width checks passed.
- Migration PHP lint: 251 files, zero syntax failures.
- Seeder PHP lint: 25 files, zero syntax failures.
- Literal seeder table/column schema references: 85 checked, zero missing references.

## Runtime gate

`php artisan migrate:fresh --seed --no-interaction` was attempted, but the uploaded snapshot does not contain `vendor/autoload.php`; Composer, PDO MySQL, and a MySQL/MariaDB server are also unavailable in the verification environment. Laravel therefore stopped before connecting to MySQL. A real runtime pass must still be executed in a local disposable MySQL database after `composer install`.

These rewritten create migrations are intended for a fresh development database. Existing deployed schemas require a separately reviewed data-migration plan.
