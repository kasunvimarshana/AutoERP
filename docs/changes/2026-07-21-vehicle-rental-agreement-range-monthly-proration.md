# Vehicle Rental agreement-range monthly proration

## Trigger

Monthly Customer Invoice and Owner Payable Voucher preparation was disabled unless the agreement contained a complete calendar month. Active agreements that started or ended mid-month therefore could not produce financial documents for their valid agreement dates.

## Business rule

Monthly billing remains calendar-month based, but each selected month is clamped to the agreement date range:

```text
period_start = later of month start and agreement start
period_end   = earlier of month end and agreement end
```

Partial months use actual calendar-day proration:

```text
prorated value = monthly value × billed calendar days ÷ calendar days in that month
```

The rule applies independently to Customer Invoice calculations and Owner Payable Voucher calculations using their respective agreements and rate versions.

## Changes

- Removed the complete-calendar-month-only UI blocker.
- Kept the simple Billing month selector.
- Display the exact agreement-clamped billing period before preparation.
- Prorate monthly base rental, monthly driver salary and monthly included-kilometre allowance by actual calendar days.
- Keep each monthly calculation inside one calendar month; longer agreement ranges are prepared as separate monthly periods.
- Apply explicit half-up rounding to six decimal places inside the Vehicle Rental calculation owner module.
- Preserve agreement-range validation, finalized Running Chart requirements, rate-version coverage, overlap checks and same-side duplicate-consumption protection.
- Preserve independent customer and owner financial processing.

## Examples

```text
Agreement: 2026-07-20 to 2026-08-19

July period:   2026-07-20 to 2026-07-31
August period: 2026-08-01 to 2026-08-19
```

For a July monthly rate of 31,000:

```text
31,000 × 12 ÷ 31 = 12,000
```

## Verification coverage

- Full-month monthly calculations preserve the existing result.
- Partial-month base rental, driver salary and included kilometres use actual calendar-day proration.
- Cross-month calculation requests fail with an instruction to split the agreement range into monthly periods.
- Frontend source contract verifies agreement-clamped monthly period selection and removal of the previous blocker.
