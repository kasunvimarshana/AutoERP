# Tenant document scanner removal

Date: 2026-07-02

## Problem

Tenant private document upload still depended on the tenant document scanner abstraction and local/ClamAV scanner configuration, even though the scanner subsystem was no longer wanted.

## Correction

Removed the tenant document scanner interface, ClamAV scanner, trusted-local scanner, scan result DTO, service-provider binding, boot-time scanner validation, and related environment/configuration keys.

Tenant, organization-unit, and user private file storage now perform only owned upload validation, checksum calculation, and private object storage. Scanner metadata was removed from models, API resources, frontend types, and document panels. Added one-table migrations to drop `scan_engine` and `scanned_at` from tenant, organization-unit, and user document tables because the original create migrations had already run in the local database.

## Verification

- `php artisan migrate --no-interaction`
- Schema check confirmed `scan_engine` is absent from `tenant_documents`, `organization_unit_documents`, and `user_documents`
- `php -l` on all changed PHP files
- Source sweep for live scanner identifiers and scanner UI/status text
- `npm run typecheck`
- `npm run lint`
- `php artisan test tests/Feature/PrivateObject/PrivateObjectStorageServiceTest.php --stop-on-failure`
- `php artisan test app/Modules/OrganizationUnit/Tests/OrganizationUnitMigrationContractTest.php --stop-on-failure`
- `APP_URL=http://localhost php artisan test tests/Feature/User/UserAccessApiTest.php --stop-on-failure`
- `git diff --check`

## Note

Running `UserAccessApiTest` without overriding `APP_URL` still fails at tenant host resolution for the known default host mismatch; the host-adjusted run passes.
