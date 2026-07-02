# Direct admin account provisioning

Date: 2026-07-02

## Problem

Tenant administrators, tenant users, and new platform operators were still created through registration invitation email flows. That delayed account creation, exposed tenant onboarding to invitation state management, and kept obsolete tenant registration invitation routes and UI active.

## Correction

Tenant onboarding now creates the initial administrator account directly with a password, active user access, and authentication credentials in one transactional provisioning path.

Tenant user creation and platform operator creation now require password confirmation and provision active credentials immediately. Platform operator invitations remain only for the existing recovery/acceptance lifecycle of invited operators.

Removed the public initial-administrator invitation API/page, tenant initial-admin invitation management APIs, tenant-user invitation resend surface, obsolete Auth registration invitation services/jobs/models/notifications/constants, and stale UI copy. The tenant onboarding state now owns `administrator_user_id` directly, and the legacy onboarding step value is renamed to `initial_admin_account`.

## Verification

- PHP syntax validation for all changed PHP files.
- `APP_URL=http://localhost php artisan test tests/Feature/User/UserAccessApiTest.php --stop-on-failure`
- `APP_URL=http://localhost php artisan test tests/Feature/Tenant/TenantActivateCommandTest.php --stop-on-failure`
- `APP_URL=http://localhost php artisan test tests/Feature/Auth/AuthErrorContractTest.php tests/Feature/Auth/AuthTrustBoundaryTest.php tests/Feature/Auth/InvitationDeliveryClaimSafetyTest.php --stop-on-failure`
- `APP_URL=http://localhost php artisan test tests/Feature/User/PlatformOperatorInvitationAcceptanceTest.php tests/Feature/User/PlatformOperatorInvitationDeliveryRecoveryTest.php tests/Feature/User/PlatformOperatorInvitationResendLifecycleTest.php --stop-on-failure`
- `APP_URL=http://localhost php artisan test tests/Feature/User/PlatformOperatorInvitationValidationTest.php tests/Feature/User/PlatformOperatorInvitationResendContractTest.php --stop-on-failure`
- `APP_URL=http://localhost php artisan test tests/Feature/Auth/AuthLoginFlowTest.php --stop-on-failure`
- `npx vitest run resources/js/modules/access/AccessPages.test.tsx --reporter=dot --silent=true`
- `npx vitest run resources/js/modules/auth/InvitationPages.test.tsx resources/js/modules/platform-administration/PlatformOperatorsPage.test.tsx resources/js/modules/platform-administration/PlatformHealthPage.test.tsx resources/js/modules/tenant/components/TenantSetupNavigation.test.ts resources/js/shared/api/apiClient.test.ts resources/js/shared/api/authRefreshCoordinator.test.ts --reporter=dot --silent=true`
- `npm run typecheck`
- `npm run lint`
- `git diff --check`
- Route and source sweeps confirmed the removed initial-administrator invitation route and `/register/invitation` page are no longer present.

## Note

The focused backend tests need `APP_URL=http://localhost` in this local workspace because `.env` points to `autoerp.tapromall.com`, which is not a central test host and fails tenant host resolution before the tested feature executes.
