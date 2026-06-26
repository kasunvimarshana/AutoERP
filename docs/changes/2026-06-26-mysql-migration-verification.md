# MySQL migration verification hardening

Date: 2026-06-26
Scope: MySQL-specific verification of the corrected Auth foundation package and the minimum related Configuration migration fix required for a clean fresh schema.

## Why

A requested `php artisan migrate:fresh --seed` verification exposed that the supplied source package intentionally excludes `vendor/`, while the isolated execution environment also has no Composer, MySQL/MariaDB server, PDO database driver, or external network access. The Artisan runtime therefore cannot boot in this environment.

A MySQL-focused static migration scan additionally found two generated foreign-key constraint names that exceeded MySQL's 64-character identifier limit. These are deterministic schema failures and were corrected at their owning migrations instead of hidden by runtime workarounds.

## What changed

- Replaced the generated foreign-key name on `auth_platform_operator_password_credentials.platform_operator_id` with the explicit portable name `auth_plat_credential_operator_fk`.
- Replaced the generated foreign-key name on `organization_unit_configuration_value_revisions.tenant_id` with the explicit portable name `org_config_revision_tenant_fk`.
- Preserved one table per migration and portable Laravel Schema Builder APIs.
- Added no compatibility patch migration; the corrected names live in the original create migrations.

## Verification performed

- Executed the exact command against an isolated extracted package: `php artisan migrate:fresh --seed --no-interaction`.
- The command stopped before Laravel boot with exit code 255 because `vendor/autoload.php` is absent.
- Confirmed the environment has no Composer, MySQL/MariaDB server binary, running MySQL service, or PDO database drivers.
- PHP syntax lint passed for all 246 migrations and 24 seeder files (270 files including `DatabaseSeeder`, zero failures).
- Migration structure scan confirmed 246 migrations and 246 unique tables, with no duplicate table creation or `Schema::table()` patch migrations.
- MySQL identifier scan confirmed no remaining generated or explicit index/constraint name above 64 characters after the two corrections.
- Decimal precision scan found no MySQL-invalid precision/scale declarations.
- Local module symbol scan for the seeding path found no missing project symbols.

## Runtime gate

A true MySQL result still requires a runtime containing the Composer-installed `vendor/` tree, PHP extensions required by `composer.json` (including PDO MySQL and BCMath), and an isolated MySQL 8/MariaDB database. Run the generated verification script in that environment. Do not point `migrate:fresh` at any database containing required data.
