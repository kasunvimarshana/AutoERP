# Remove Vehicle Rental insurance assignment blocker

## Request

Allow a vehicle to be assigned for a rental period without requiring an active insurance document covering the complete requested period.

## Change

Vehicle Rental legal-document availability now requires only an active revenue-licence document covering the requested rental period.

Insurance remains a Vehicle-owned document type and can still be recorded, viewed, updated, downloaded, expired, or revoked through the Vehicle module. It no longer blocks Vehicle Rental assignment creation or replacement.

## Preserved controls

- Revenue-licence coverage validation remains active.
- Vehicle assignment overlap prevention remains active.
- Ownership and owner-supply coverage validation remain active.
- Vehicle and driver timeline validation remains active.
- Workshop, breakdown, and shared availability blockers remain active.
- Tenant and organization-unit boundaries remain unchanged.

## Regression protection

`RentalLegalDocumentAvailabilityPolicyTest` verifies that the Rental availability policy requires revenue licence and does not require insurance.
