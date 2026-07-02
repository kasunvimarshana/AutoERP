# Tenant domain rejection guidance

Date: 2026-07-02

## Problem

Tenant domain creation rejected platform hosts, localhost, IP addresses, single-label names, reserved suffixes, and malformed hostnames through the Tenant module's backend normalizer. Several distinct root causes were collapsed into the generic message `A valid public custom hostname is required.`, making platform/local routing mistakes hard to understand from the UI error.

## Correction

Kept tenant-domain validation strict and backend-owned, but split the `TenantValueNormalizer` domain checks into explicit branches with focused messages for platform hosts, local/reserved hostnames, IP addresses, single-label hostnames, length violations, and malformed hostnames.

Added Tenant domain rule coverage to verify the user-facing rejection reason for platform hosts, IP addresses, reserved local hostnames, and single-label hostnames.

## Verification

- `php -l app/Modules/Tenant/Services/Rules/TenantValueNormalizer.php`
- `php -l app/Modules/Tenant/Tests/TenantDomainRulesTest.php`
- `php artisan test app/Modules/Tenant/Tests/TenantDomainRulesTest.php --stop-on-failure`

## Note

`vendor\bin\pint.bat app/Modules/Tenant/Services/Rules/TenantValueNormalizer.php app/Modules/Tenant/Tests/TenantDomainRulesTest.php` could not run because PHP reported `errno=28 No space left on device` while loading the Pint PHAR. `Get-PSDrive` showed the C: drive had 0 free bytes at the time of verification.
