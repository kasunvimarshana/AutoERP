# Add agreement-context Vehicle Rental vehicle selection

## Request

Keep the Vehicle Rental user-facing workflow aligned with the uploaded videos and avoid requiring users to navigate a separate technical workflow just to select a vehicle for an agreement.

## Root cause

The fresh Vehicle Rental interface exposed assignment creation only from the standalone Vehicle Assignments workspace. An active customer or owner agreement did not provide the demonstrated practical flow of opening the agreement and selecting its vehicle. Users therefore had to reselect the assignment side and agreement even though both were already known.

## Change

- Active customer and owner agreements now expose a `Select vehicle` action when the user has Vehicle Rental assignment-management permission.
- The assignment dialog opens with the agreement and correct assignment side already selected.
- Agreement-context assignment creation hides the technical assignment-side selector and locks the agreement identity.
- The standalone Vehicle Assignments workspace remains available for timeline review, custody, return, replacement, cancellation, and exceptional direct assignment management.
- Agreement lifecycle actions remain governed independently by agreement-management permission.

## Preserved controls

- Backend assignment validation remains authoritative.
- Vehicle and driver overlap prevention is unchanged.
- Owner-supply source coverage, ownership, availability, agreement-period, tenant, organization-unit, and optimistic-concurrency controls are unchanged.
- No database relationship, migration, API contract, calculation, Invoice, Payment, Tax, or Finance behavior changed.

## User workflow

```text
Open active agreement
→ Select vehicle
→ Enter vehicle, period, driver/self-drive, and handover odometer
→ Save
```

The backend continues to maintain effective-dated assignment history without exposing that implementation detail as an additional user workflow.
