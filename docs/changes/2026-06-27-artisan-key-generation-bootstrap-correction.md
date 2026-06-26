# Artisan key-generation bootstrap correction

Date: 2026-06-27

## Context

Running `php artisan key:generate` with the normal blank `APP_KEY` bootstrap state failed before Laravel could execute the command. Artisan was constructing registered module commands, and several command constructors eagerly resolved full Tenant/Auth runtime service graphs. The Tenant lifecycle graph reached `RegistrationInvitationService`, which requires `OpaqueTokenCodec`; the codec correctly rejected the blank application key. This created a bootstrap cycle: the command needed to generate the application key could not start until the key already existed.

## Decisions

- Keep `OpaqueTokenCodec` strict. A missing or short application key must still fail whenever Auth cryptographic functionality is actually used.
- Do not introduce a placeholder key, fallback digest, environment bypass, or compatibility patch.
- Keep Artisan command constructors dependency-free. Laravel runtime services are resolved through `handle()` method injection only when the selected command executes.
- Apply the rule consistently to all existing Auth and Tenant commands rather than patching only the command that exposed the failure.

## Changes

- Moved runtime dependencies from constructors to `handle()` injection in:
  - `AuthClientCreateCommand`
  - `AuthRetentionPurgeCommand`
  - `TenantCreateCommand`
  - `TenantActivateCommand`
  - `TenantSuspendCommand`
  - `TenantDeactivateCommand`
- Added an architecture regression test that rejects required runtime-service dependencies in module command constructors.
- Corrected the stale `OpaqueTokenCodecTest` exception expectation to the authoritative `ConfigurationException` contract.
- Documented the command-bootstrap rule beside Auth and Tenant command registration.

## Verification

- All production Artisan command classes now have dependency-free constructors.
- Changed PHP files pass syntax validation.
- `OpaqueTokenCodec` continues to reject missing or insufficient application keys when resolved for Auth use.
- No migrations, database relationships, token formats, or security policies were changed.

## Runtime boundary

The uploaded source package does not include Composer `vendor`, so the real Laravel `key:generate` command could not be executed in this environment. The required local verification is:

```bash
php artisan optimize:clear
php artisan key:generate
php artisan auth:readiness
```
