# Platform operator invitation removal

Date: 2026-07-02

## Problem

Platform operators had already moved to direct password-backed creation, but the old invitation acceptance, resend, revoke, and recovery-registration surfaces still existed. That kept a second account lifecycle alive and allowed platform operators to be moved back into an invited state.

## Correction

Removed the platform operator invitation subsystem from the User module: public inspect/accept routes, protected resend/revoke routes, security-recovery route, invitation services, token codec, delivery job, notification, models, constants, frontend registration page, invitation API helpers, and invitation UI actions.

Platform operators now have only active/inactive lifecycle states in application code. The operator create flow remains the single path for provisioning platform credentials. Existing `invited` platform operators are normalized to `inactive`, and obsolete platform invitation tables are dropped through explicit migrations for already-migrated databases.

Platform health no longer requires a platform operator invitation URL. The sessions screen now focuses on session revocation only.

## Verification

- PHP syntax validation for all changed PHP files.
- `APP_URL=http://localhost php artisan test tests/Feature/Auth/AuthTrustBoundaryTest.php tests/Feature/Auth/AuthLoginFlowTest.php tests/Feature/User/UserAccessApiTest.php --stop-on-failure`
- `npx vitest run resources/js/modules/platform-administration/PlatformOperatorsPage.test.tsx resources/js/modules/platform-administration/PlatformHealthPage.test.tsx resources/js/shared/api/apiClient.test.ts resources/js/shared/api/authRefreshCoordinator.test.ts --reporter=dot --silent=true`
- `npm run typecheck`
- `npm run lint`
- `php artisan route:list --path=operator-invitations`
- `php artisan route:list --path=security-recovery`
- `php artisan route:list --path=invitation`
- Source sweeps confirmed no live platform operator invitation route, page, API helper, service, or model references remain.
- `git diff --check`

## Note

The focused backend tests use `APP_URL=http://localhost` for this local workspace because `.env` points to `autoerp.tapromall.com`, which is not a central test host for tenant-auth test requests.
