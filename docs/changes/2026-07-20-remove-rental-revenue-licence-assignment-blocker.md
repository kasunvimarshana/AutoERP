# Remove Vehicle Rental revenue-licence assignment blocker

## Request

Allow Vehicle Rental assignments and replacements without requiring a revenue-licence document that covers the requested rental period.

## Change

Removed the Vehicle Rental legal-document availability blocker and its service-provider registration. Vehicle Rental no longer blocks assignment or replacement based on Insurance or Revenue Licence document validity.

Insurance and Revenue Licence remain Vehicle-owned document types. They can still be recorded, viewed, updated, downloaded, expired, or revoked through the Vehicle module; they are no longer Rental assignment eligibility rules.

## Preserved controls

- The selected vehicle must remain operationally active.
- Vehicle assignment overlap prevention remains active.
- Ownership and owner-supply coverage validation remain active.
- Vehicle and driver timeline validation remains active.
- Workshop, breakdown, and other shared availability blockers remain active.
- Tenant and organization-unit boundaries remain unchanged.
- Vehicle document storage and lifecycle behavior remain unchanged.

## Regression protection

`RentalLegalDocumentAvailabilityPolicyTest` verifies that the Vehicle Rental service provider does not register a legal-document availability blocker and that the obsolete blocker class is absent.

## Schema impact

No database migration or historical-data change is required.
