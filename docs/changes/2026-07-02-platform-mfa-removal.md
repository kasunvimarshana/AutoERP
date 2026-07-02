# Platform MFA Removal

Date: 2026-07-02

## Problem

Platform multi-factor authentication was still part of the Auth schema, login flow, invitation acceptance, account recovery wording, frontend forms, environment settings, permissions, and tests.

## Correction

Removed the platform MFA subsystem instead of disabling it behind compatibility flags. Platform operators now authenticate with their recipient-owned password, and sensitive platform actions continue to require recent platform authentication through the renamed `platform_step_up` configuration.

Deleted the MFA method table migration, model, status constants, TOTP/MFA services, enrollment controller/request, MFA policy test, and User/Auth MFA contract. Removed MFA fields from platform sessions, token payloads, session presentation, login requests, public route classification, platform permissions, and invitation acceptance responses.

Updated platform account recovery to revoke credentials and sessions only, then issue a recipient-owned password setup invitation. Updated UI copy, navigation, tests, README guidance, `.env.example`, and the local `.env` MFA flags.

## Verification

- `php -l` on 26 changed PHP files
- `APP_URL=http://localhost php artisan test tests/Feature/Auth/AuthLoginFlowTest.php --filter=platform_login --stop-on-failure`
- `APP_URL=http://localhost php artisan test tests/Feature/Auth/AuthErrorContractTest.php tests/Feature/User/PlatformOperatorInvitationAcceptanceTest.php tests/Feature/User/PlatformOperatorInvitationResendLifecycleTest.php tests/Unit/Auth/OpaqueTokenCodecTest.php --stop-on-failure`
- `APP_URL=http://localhost php artisan route:list --path=api/v1/platform/auth`
- `npx vitest run resources/js/modules/auth/InvitationPages.test.tsx resources/js/shared/api/apiClient.test.ts resources/js/shared/api/authRefreshCoordinator.test.ts --reporter=dot --silent=true`
- `npm run typecheck`
- `npm run lint`
- `git diff --check`
- Source sweep for non-historical MFA identifiers outside append-only `/docs/changes` and archived audit reports

## Note

Running the full `AuthLoginFlowTest` without overriding `APP_URL` still fails before auth logic because the default request host is `autoerp.tapromall.com`, while configured central hosts are `127.0.0.1, localhost`.
