# Initial administrator invitation draft-tenant inspection

Date: 2026-07-01

## Problem

The complete administrator registration page rejected a valid initial administrator invitation with `AUTH_INVITATION_INVALID`.

## Root cause

Initial administrator invitation inspection looked up the invited tenant through the active-only authentication directory. During onboarding, the tenant is still `draft`, so a pending, unexpired invitation for that draft tenant was incorrectly hidden from the public registration flow.

## Correction

`RegistrationInvitationService` now uses the general tenant summary directory when displaying initial administrator invitation details. Runtime authentication flows still use the active-only tenant authentication directory.

## Verification

- `php artisan test tests/Feature/Auth/InitialAdministratorInvitationInspectionTest.php tests/Feature/Auth/InitialAdministratorInvitationValidationTest.php`
- `php -l app/Modules/Auth/Services/Registration/RegistrationInvitationService.php`
- `php -l tests/Feature/Auth/InitialAdministratorInvitationInspectionTest.php`
- Verified the current local pending invitation inspects successfully through `InitialAdministratorRegistrationService`.
