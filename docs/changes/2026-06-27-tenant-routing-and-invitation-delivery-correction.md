# Tenant routing guidance and registration invitation delivery correction

Date: 2026-06-27

## Context

Platform Tenant Setup Step 3 accepted an IP-shaped value in the browser and relied on the backend to reject it as an invalid public custom hostname. The page explained that local/testing uses a tenant-code fallback, but it did not load or display the backend routing-readiness state or provide the exact non-secret configuration required for the selected tenant. This made `127.0.0.1` look like a value that should be stored as a tenant domain even though the domain model intentionally permits only public fully qualified hostnames.

Separately, queued tenant registration invitation delivery failed because the Auth delivery service eager-loaded `invitation.tenant`. `AuthRegistrationInvitationModel` intentionally has no Tenant-model relationship, so Eloquent raised `RelationNotFoundException` before the email could be handed to the mail transport.

## Decisions

- Keep public tenant-domain validation strict. Never store localhost, IP addresses, reserved local/test suffixes, protocols, ports, paths, queries, or fragments as tenant domains.
- Keep local/testing routing explicit and tenant-code based. Do not add synthetic localhost domain rows or production validation bypasses.
- Make Step 3 a routing step rather than a domain-only step and render the backend-authoritative routing mode.
- Keep the Auth model free of concrete Tenant relationships. Resolve display data through the existing neutral `TenantDirectoryInterface` implemented by the Tenant module.

## Changes

- Added local-fallback diagnostics to `TenantRoutingReadinessPolicy`: environment support, enabled state, configured tenant code, and selected-tenant match state.
- Updated Platform Tenant Step 3 to show one of three authoritative states: verified public domain, ready local/testing fallback, or actionable unavailable routing.
- Added copyable local/testing environment values for the selected tenant code when fallback configuration is incomplete or mismatched.
- Added browser-side rejection for IPv4 addresses, localhost, and reserved public-domain suffixes with guidance to use routing readiness.
- Renamed the primary UI action from a generic tenant hostname to a public tenant hostname.
- Replaced the invalid `invitation.tenant` eager load with `TenantDirectoryInterface::summary()` in the invitation delivery service.
- Added routing-presentation, routing-policy, component, and Auth boundary regression coverage.

## Verification

- Changed PHP files pass syntax validation.
- TypeScript semantic check passes with zero diagnostics.
- ESLint passes with zero errors and zero warnings.
- Targeted tenant routing tests pass: 2 files, 11 tests.
- Full frontend suite passes: 48 files, 164 tests.
- Production Vite build passes: 654 modules.
- Auth boundary source check confirms invitation delivery uses `TenantDirectoryInterface` and does not restore a concrete Tenant Eloquent relationship.
- No migrations, domain persistence rules, tenant-isolation rules, invitation token formats, invitation expiry rules, or mail security settings changed.
