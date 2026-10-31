# MySQL test-bootstrap safety: validated target cannot be overridden by .env — 2026-11-01

## Investigated root cause

The repository's `phpunit.mysql.xml` uses `tests/bootstrap/mysql.php` as its bootstrap. That bootstrap checked `DB_CONNECTION`, the disposable `DB_DATABASE` suffix `_test`, and an empty `DB_URL` **before Laravel loads the project's .env**. Previously it neither required an explicitly selected `DB_HOST` nor pinned the verified values in PHP's environment stores. An unset process-level `DB_URL` or `DB_HOST` could therefore be supplied later by an unsafe application `.env` file. This is unacceptable for a destructive test suite using migrations.

## Owner-module fix

- Require the operator to explicitly set `DB_HOST` in the process environment instead of silently inheriting the application `.env` host.
- Once the driver, database, host and empty URL have passed validation, pin them in `getenv()`, `$_ENV` and `$_SERVER` **before** requiring Composer's autoloader. Laravel's standard immutable dotenv loader cannot overwrite these validated values.
- Preserve the original case of `DB_DATABASE`; apply lowercase only when checking the `_test` suffix. Changing case could silently redirect to a different database on case-sensitive MySQL servers.
- Retain the existing fail-closed driver/DB URL/disposable database guards. The test bootstrap never invents credentials, performs migrations or overrides authorized Laravel production configuration.

The change applies only to `tests/bootstrap/mysql.php`, where the responsibility belongs. It does not change application runtime or an existing production database.

## Tests actually run

Used PHP 8.4.24 and Python's standard library in the isolated Linux execution sandbox. Copied **exact GitHub blobs** (`git hash-object` matched the GitHub blob IDs) into a temporary filesystem with a stub `vendor/autoload.php`. The stub only reports PHP's environment stores and does not connect to any database:

- `tests/bootstrap/mysql.php` blob: `9a371416d625b4a18621905a0f3c0fe96d39202c`
- `tests/bootstrap/test_mysql_bootstrap.py` blob: `559aa1294a08580db2d0678ce98857715c7f6bfa`
- `php -l tests/bootstrap/mysql.php`: **No syntax errors**.
- `python -B -m unittest discover -s tests/bootstrap -p 'test_*.py' -v`: **7/7 passed** in 0.095 seconds. Cases: unsupported driver, unsafe database name, explicit DB URL, missing host, valid MySQL pinning, valid MariaDB pinning, preservation of case-sensitive database name.

These are real focused test results for the exact referenced blobs, **not** a new end-to-end Laravel/MySQL test run.

## Local environment and release boundary

The known Windows checkout is `D:\\project-new\\kasun\\AutoERP` (an alternate historical path is `D:\\DP Project\\kasun\\3005\\AutoERP`). This tool session's Linux container has no Windows drive mount or local repository checkout, cannot resolve `github.com` for git transport, and lacks Composer, the full vendor tree and PDO database drivers. The connected GitHub integration can read and write repo files but cannot run processes on the Windows host. Thus the full local PHP, MySQL, frontend, integration and historical Finance tests **were not** executed from the user's Windows environment in this session.

This is a reproducible safety improvement, not a replacement for independent approval, historical Purchase source-currency evidence, balanced Finance/GL tie-out, full Rental settlement workflows, production acceptance or successful mandatory checks. Do not trigger GitHub Actions or promote the authoritative release branch based on this focused evidence.
