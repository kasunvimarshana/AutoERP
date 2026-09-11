# Base rental estimation — actual calendar days v1

Implemented after `41dbdef2dc69c67ffbf5ba323b48b726566f3843`. This is an authenticated, read-only base-rent calculation available in both Customer and Owner Agreement review. It is not an invoice, payment, usage-consumption record or automatic agreement amendment.

## Decision and rationale

The user authorized documented, defensible domain decisions when additional project evidence cannot be supplied. Select actual civil days for this named estimation policy. Monthly cycles follow the agreement's start-date anniversary; shorter months clamp the anniversary to their last date without changing the original anchor. This supports the non-calendar cycles observed in TACGL E09/E10 and avoids inventing a universal fixed-30 divisor from E11/E12. The specific estimation policy is a new documented design decision, not a retroactive assertion about those historical transactions.

[Malkey's with-driver terms](https://www.malkey.lk/rates/with-driver-rates/) provide a commercial example of consecutive calendar-day measurement. [Stripe's proration documentation](https://docs.stripe.com/billing/subscriptions/prorations) demonstrates explicit billing-period boundaries and configurable granularity. Neither source makes this the only possible Rental contract policy. The UI states the estimation method and scope before submission; the API requires `actual_calendar_days_v1` explicitly and accepts no silent fallback.

## Calculation contract

- Input `from` and `until` are strict ISO civil dates, both inclusive, fully covered by the selected agreement. A same-day period represents one day. Physical timestamp offsets, custody boundaries and executing dates do not select commercial dates automatically.
- Use the selected agreement's recorded `basis`, `base_rate` and currency snapshot. Never read the other side's terms or accept a client-provided rate/total. A missing base rate is an error; explicit zero is valid.
- Daily: multiply recorded daily base rate by the included civil-day count.
- Monthly: divide the selected dates into their actual anniversary cycles. January 31 anchors January 31, February 28/29, March 31, and so on. Never iterate February's clamped date as the new anchor.
- Each monthly cycle uses its actual elapsed civil-day count as denominator. Full cycles return exactly one recorded monthly rate. Partial cycles receive the corresponding fraction; no minimum rental, grace time, fixed-day divisor or replacement surcharge is assumed.
- Use exact decimal arithmetic. Let `C(k) = truncate_to_six_decimals(rate × k / cycle_days)` for cumulative days since cycle start. A segment from day offset `a` through offset `b` costs `C(b) − C(a)`. This explicit cumulative quantization prevents splitting a cycle into adjacent previews from losing a fractional residual. It is an allocation rule for the estimate, not statutory tax rounding.
- Sum the segment amounts exactly. Return each segment's dates, cycle dates, day count, denominator and amount, with agreement identity/version, selected policy, rate and currency.

An interactive preview must span less than ten years (`MAX_PREVIEW_YEARS`). This named resource guard bounds response size/work; it does not shorten agreements or prohibit longer contracts. Split longer estimates into periods. The original anchor still controls each split. Date arithmetic uses UTC solely as a neutral civil-date representation, not as an assumption about the user's physical rental timezone.

## API and authorization

`POST /api/v1/vehicle-rental/{customer|owner}/agreements/{agreement}/base-rent-preview`

```json
{
  "expected_version": 2,
  "policy": "actual_calendar_days_v1",
  "from": "2026-01-31",
  "until": "2026-02-27"
}
```

The existing authenticated tenant/organization/feature middleware and side-specific agreement **view** permission apply. The server resolves and scopes the agreement before calculation. Stale expected versions return 409; invalid policy, rate or dates return 422; inaccessible records return 404 and missing permission returns 403. Unknown client monetary fields do not influence the result.

The response is wrapped in `data`. Draft, Active and Closed agreements can be estimated from their currently recorded terms. A preview creates no history or financial writes. A concurrent draft edit after the read does not mutate the returned snapshot: the response identifies the read revision. Any future financial creation must independently revalidate its source and policy under the appropriate transaction; a preview is not a reservation or entitlement to invoice.

## UI

Open an agreement and expand **Estimate base rent**. Select both dates and calculate. The form shows the policy, limited scope, currency, source revision and period breakdown. It disables calculation for an unknown rate, clears results when dates change, displays server validation/conflict errors and aborts the request when the agreement panel unmounts. Switching agreement or revision remounts the form. No raw foreign-key entry or account selection is introduced.

## Verification and boundaries

Tests cover leap/non-leap February, 31-day months, month-end anchor recovery, complete and partial cycles, exact adjacent-period reconciliation, daily E04 arithmetic, a same-day rental, zero versus unknown rates, customer/owner independence, invalid dates/policy, agreement coverage and stale versions. Authenticated HTTP coverage includes permissions, tenant isolation and no invoice/history creation. Frontend tests cover the visible breakdown, revision forwarding, unknown/zero distinction, error handling, changed-period invalidation and aborted requests.

No schema or relationship changes. Rental owns this estimate and uses Core decimal arithmetic. Tax, Invoice, Payment and Finance ownership stays unchanged.

Mileage, driver/OT, AC, night-out, replacement charging, downtime deductions, deposits, tax and downstream financial creation are not included in `base_rent`. Do not display it as an all-inclusive rental bill. The wider commercial and release requirements remain governed by the module TODO; this endpoint cannot substitute for their implementation or UAT.
