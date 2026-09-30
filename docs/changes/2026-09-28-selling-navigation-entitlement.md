# Register Selling route entitlements

## Change

- Registered tenant access policies for the Selling workspace and sale detail routes.
- Required the Selling and supporting tenant modules, an organization unit, and an applicable Selling permission, matching the Selling navigation configuration.
- Added route-resolution and navigation regression coverage.
- No database migration is needed; the issue was a missing frontend route-entitlement registration.

## Reason

The Selling sidebar link used the fail-closed tenant route resolver. Since `/selling` had no registered route entitlement, the resolver removed the link even when its permissions and plan modules were enabled.

## Notes

No previous change record was modified.
