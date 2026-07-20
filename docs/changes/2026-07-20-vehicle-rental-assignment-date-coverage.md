# Separate Vehicle Rental planning dates from operational timestamps

## Request

Make owner-supply source selection practical for customer-use planning when the assignments cover the same calendar dates, while preserving exact operational integrity at handover and replacement.

## Root cause

Rental assignment creation stores a planned assignment first and activates it only when custody is handed over. The owner-supply source lookup and backend validation nevertheless required an already-active source and compared exact timestamps. As a result:

- a planned owner-supply assignment could not be selected while planning the linked customer-use assignment;
- same-day periods could be rejected only because their clock times differed;
- changing the selected vehicle or planning dates could leave already-loaded lookup options visible until the lookup was reopened.

Planning eligibility and operational eligibility were being treated as the same rule even though they represent different lifecycle stages.

## Change

- Customer-use planning may reference an owner-supply assignment in `planned` or `active` status.
- Planning coverage compares calendar dates:
  - owner source start date must be on or before the customer-use start date;
  - owner source end date must be open-ended or on or after the customer-use planned end date;
  - an open-ended customer-use plan still requires an open-ended owner source.
- Customer vehicle handover and immediate vehicle replacement require an `active` owner-supply source.
- Operational handover and replacement coverage continues to compare exact timestamps.
- Source lookup results are invalidated when vehicle, planned start, or planned end changes.
- The server owns source-status eligibility by lookup purpose; the frontend no longer hardcodes `active` for the planning lookup.

## Preserved controls

- Same-vehicle source matching remains mandatory.
- Vehicle and driver overlap checks continue to use exact timestamps.
- Agreement-period, ownership, availability, tenant, organization-unit, and concurrency validation remain authoritative.
- A planned owner source cannot be used to hand over a customer vehicle.
- A source that covers the calendar dates but not the exact operational handover/replacement period is rejected at the operational transition.
- Company-owned customer-use assignments may continue without an owner-supply source when company ownership covers the period.

## User workflow

```text
Plan owner vehicle supply
→ Plan customer vehicle use on the covered calendar dates
→ Hand over owner supply
→ Hand over customer vehicle
```

The user-facing agreement-first flow remains unchanged; only lifecycle eligibility and period semantics are corrected.

## Relationship review

No database relationship or schema changed. The existing customer-use assignment to owner-supply source-assignment relationship remains the single source link. The change separates planning and operational validation policies without duplicating that relationship or moving responsibility outside Vehicle Rental.
