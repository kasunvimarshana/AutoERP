# Service job vehicle registration label

## Context

The selected vehicle field on the service job form showed the internal vehicle number and model, even when a registration number was available.

## Changes

- Display the vehicle registration number as the service job vehicle lookup label, falling back to the existing vehicle name or internal code when no registration number is recorded.
- Update the lookup placeholder to invite searches by registration number or model.

## Reason

The registration number is the identifier users recognize when selecting a vehicle. The internal vehicle number remains available as a fallback for vehicles without a registration number.
