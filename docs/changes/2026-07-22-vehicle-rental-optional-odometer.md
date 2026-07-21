# Vehicle Rental optional odometer workflow

## Business rule

A physical vehicle may not have a usable odometer. Missing readings must remain unavailable facts, not fabricated zero values.

The existing Vehicle master `odometer_reading` is the current source of truth:

- a decimal value, including `0`, means the odometer is available;
- `null` means the odometer is unavailable;
- no additional user-facing status or approval workflow is introduced.

Each custody event and Daily Running Chart keeps its own immutable reading snapshot. Later Vehicle master changes do not rewrite historical records.

## User workflow

### Vehicle with an available odometer

- Handover and return require a reading.
- A new Daily Running Chart requires End KM.
- Start KM may remain blank and is resolved by the backend from the previous finalized chart, then the assignment handover, then the current Vehicle reading.
- A manually entered Start KM remains an exception override.
- A lower reading remains blocked.
- An upward gap still requires a variance reason.

### Vehicle without an available odometer

- Handover, return, replacement and Daily Running Chart forms hide kilometre inputs.
- The corresponding database values remain `null`.
- Fixed daily and monthly rental calculations continue.
- A calculation with an `excess_km` rate fails clearly because verified distance is required.

An unmeasured finalized chart breaks the old continuity chain. When a usable or replacement odometer is later recorded on the Vehicle master, the next measured chart begins from the new current baseline instead of being forced back to an obsolete reading.

## Ownership

- Vehicle owns the current odometer reading availability.
- Vehicle Rental owns custody and Running Chart snapshots.
- Vehicle Rental calculation owns whether a rate requires verified distance.
- Reporting preserves unavailable measurements as `null` instead of displaying a false zero.

## Data migration

The deployment migrations make these existing columns nullable:

- `vehicle_rental_custody_events.odometer`
- Running Chart start, end, total, garage and commercial kilometres
- Calculation commercial and excess kilometres

No existing value is rewritten. Existing zero readings remain zero. Rollback fails explicitly when nullable operational facts already exist rather than fabricating values.

## UAT checklist

1. Create a Vehicle with a blank odometer and confirm the API keeps `odometer_reading = null`.
2. Plan and hand over that Vehicle without an odometer reading.
3. Create and finalize a Daily Running Chart without kilometre fields.
4. Run a fixed daily or monthly calculation successfully.
5. Add an excess-kilometre rate and confirm the calculation is blocked with the verified-distance message.
6. Create a working-odometer Vehicle and confirm Start KM can remain blank while End KM is required.
7. Finalize a second chart and confirm Start KM continues from the previous finalized End KM automatically.
8. Enter a lower Start KM and confirm it is blocked.
9. Enter a higher Start KM and confirm the variance reason is required.
10. Return and replace both measured and unmeasured vehicles and confirm each side follows its own odometer availability.
11. Confirm Running Chart reports leave unavailable kilometre cells blank rather than showing zero.
