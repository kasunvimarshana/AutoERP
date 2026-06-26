# Platform operator invitation acceptance correction

Date: 2026-06-27

## Context

Valid platform operator invitation links could be inspected, but registration failed during acceptance for two independent reasons:

- Public invitation acceptance attempted to record an authenticated platform audit event. No current user context exists before the recipient has signed in, so the audit recorder threw and rolled back the transaction.
- The HTTP request validated only password confirmation and length. The credential service then rejected policy violations with an internal exception, which the global API handler correctly treated as an unexpected server failure.

## Decisions

- Keep invitation acceptance public and invitation-token authenticated. Do not create or fake an authenticated request user context.
- Record the acceptance with a trusted explicit platform actor snapshot representing the newly activated operator.
- Keep the credential service password-policy assertion as defense in depth.
- Enforce the same password policy at the request boundary so invalid user input returns field-level validation errors.
- Return the server-owned password requirements from invitation inspection and render them in the registration UI.
- Suppress function arguments in exception traces so invitation tokens and passwords cannot be written to application logs during future failures.

## Changes

- Added a narrow platform audit actor contract for trusted pre-authentication and integration actions.
- Changed invitation acceptance auditing to use the activated operator snapshot instead of the missing request user context.
- Applied `PasswordPolicy::rule()` to the platform invitation acceptance request.
- Exposed the current password-policy requirements through the invitation inspection response.
- Updated the registration page to show the exact minimum length and required character classes.
- Enabled `zend.exception_ignore_args` during Laravel bootstrap for HTTP and Artisan execution.

## Verification

- Changed PHP files pass syntax validation.
- Audit interface and implementation method signatures match.
- Platform invitation acceptance no longer calls the authenticated platform audit path.
- Password-policy violations are rejected at request validation before credentials are provisioned.
- Frontend types and invitation-page syntax parse successfully.
- Laravel bootstrap now guarantees exception arguments are hidden.
- No migrations, token formats, invitation hashes, expiry rules, or MFA security rules changed.
