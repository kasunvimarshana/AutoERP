# Guarded MySQL and MariaDB verification profile

Date: 2026-07-12

## Problem

The default PHPUnit profile intentionally uses in-memory SQLite. That suite is fast, but it cannot verify production-database behavior such as MySQL/MariaDB foreign keys, collations, unique-index semantics, row locking, and transaction behavior.

## Correction

- Added `phpunit.mysql.xml` as a separate production-database verification profile.
- Added `tests/bootstrap/mysql.php` to fail closed unless the connection is `mysql` or `mariadb`.
- The bootstrap rejects `DB_URL` so the guarded `DB_DATABASE` value remains authoritative.
- The bootstrap requires the database name to end with `_test`, preventing accidental execution against a normal application database.
- Database host, port, name, username, and password are supplied through the execution environment; none are stored in source.
- Added `composer test:mysql` as the single command for this profile.
- Added a source contract test that protects the disposable-database and no-credentials invariants.

## Usage

Set `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for a disposable database whose name ends with `_test`. Keep `DB_URL` empty, then run:

```bash
composer test:mysql
```

## Scope

The normal SQLite suite remains unchanged. This profile adds database parity verification; it does not replace the fast suite and does not configure a shared or production database.

## Verification

- PHP syntax validation passed for the bootstrap and contract test.
- XML parsing passed for `phpunit.mysql.xml`.
- `composer.json` was re-fetched and validated as complete JSON with the new command.
- The MySQL/MariaDB suite itself requires an external disposable database and was not executed from the connector environment.
