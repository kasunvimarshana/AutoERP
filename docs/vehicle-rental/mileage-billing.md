# Commercial mileage assessment

## Selected policy and evidence

The named, explicitly accepted policy is `commercial_calendar_cycles_v1`. This is a documented implementation choice under the user's authorization to resolve missing commercial rules. It is not a retrospective claim about every TACGL tariff. TACGL distinguishes observed distance, included KM and excess-KM rates, while E08 demonstrates that a whole-distance charge must not automatically be relabelled excess distance. This workflow therefore requires explicit `commercial_km`, `included_km` and `excess_km_rate` and never substitutes odometer distance, subtracts garage KM automatically or imports a historical narrative amount.

A primary-source recheck on 2026-09-15 found that [Malkey's self-drive rate page](https://www.malkey.lk/rates/self-drive-rates/) presents monthly/weekly vehicle prices alongside a daily excess-mileage allowance. This demonstrates that rental-price frequency alone is not a universal industry definition of allowance frequency. No published operator allowance or rate is seeded into AutoERP. The convention below is explicitly displayed and selected; agreements whose actual tariffs use another convention must not be represented as accepting this one.

## Convention

- Daily basis: one included-KM allowance per civil calendar day.
- Monthly basis: one allowance per anniversary cycle anchored on the original agreement start date. Use original-anchor no-overflow month arithmetic; Jan 31 → Feb 28 → Mar 31 does not drift to Mar 28.
- A contractually shortened final monthly cycle receives `included_km × covered civil days ÷ complete cycle civil days`, quantized to six decimals. Invoice splitting, early chart billing or a replacement does not shorten the agreed cycle or create another allowance. Workflow closure time is not invented as a contractual end date.
- The initial timezone comes from the resolved organization/tenant `localization.timezone` configuration and is shown in the quote. The first assessment freezes it for that side's agreement, including retained void history. Later workspace timezone changes do not reinterpret existing commercial periods. The command checks the displayed timezone and pool revision again.
- Pool key: commercial side + agreement + cycle start. Customer charts across original/replacement vehicles share that customer agreement's allowance. Owner allowance belongs to its separate owner agreement. There is no cross-side or cross-cycle carry-forward.
- A finalized chart must lie wholly within one covered cycle in that timezone, with an exclusive end allowed exactly at the next cycle boundary. A cross-boundary quantity is not apportioned by elapsed time: record defensible split physical evidence through the existing correction flow instead of fabricating kilometre distribution.
- Consume allowance in assessment order. This makes the total excess for a fully assessed cycle independent of invoice splitting. Do not infer chronological driving order from invoice creation order.

## Exact assessment and correction

Let `D` be surviving previously assessed commercial KM in the cycle, `d` this chart's commercial KM, `A` the cycle allowance, `r` its excess rate and `P` the sum of surviving prior assessment amounts. The new amount is:

`truncate6(max(D + d − A, 0) × r) − P`

Store original distance, allowance, included distance applied, excess distance, prior totals, preceding pool head, rate, amount, policy, timezone and chart/agreement revisions. Cumulative quantization preserves residuals: two `0.000001 km` observations at `0.500001/km`, with zero allowance, assess zero then `0.000001` rather than lose both sub-scale contributions.

Zero distance, zero rate and an allowance-covered distance remain distinct known cases. A zero-cost assessment still persists and consumes its source/allowance but creates no Invoice. It blocks duplicate assessment and physical chart reversal until explicitly voided. Unknown commercial KM, allowance or rate fails instead of becoming free, unlimited or chargeable by default. Unsupported monetary overflow fails atomically.

Positive assessments create a canonical Invoice draft with the existing side's Tax/Finance profile in the same transaction. The document supply period reflects actual chart coverage; the allowance-cycle dates remain assessment context. Canonical Invoice approval/posting/reversal and payment ownership remain unchanged. Document fields are supplied explicitly and used only if an invoice is due.

Cancellation/reversal of an Invoice does not remove its assessment from the allowance pool. Reissue preserves the calculation. To correct an assessment, release its financial documents and void it with revision, reason and actor. **Void later surviving mileage assessments in the same cycle first.** Their prices depend on the earlier allocation; removing an early free-KM assessment while keeping later excess charges would overbill. The reverse-order guard preserves those dependencies without rewriting financial history. Zero-cost assessments can be voided without a nonexistent invoice; they cannot be reissued as zero invoices. Other usage components and the opposite side remain independent.

## Ownership, API and UI

Rental owns allowance allocation and immutable assessment evidence. Reuse the existing customer/owner usage-charge tables and `excess_distance` component identity; no allowance balance cache, reverse Invoice relationship or duplicate party/vehicle entity is added. One indexed cycle lookup supports cumulative arithmetic and correction ordering. Two explicit upgrade migrations add the customer/owner cycle indexes through the established `UpgradeMigrations` provider path, preserving published table-creation history.

Existing vehicle → agreement → chart → charge locks serialize assessment, correction and physical evidence reversal. A command includes expected chart/agreement revisions, the quote's pool head and its timezone. Another assessment or timezone change requires a new quote. Invoice creation failure rolls back the assessment. The operator selects existing chart/side context rather than typing IDs.

Under `/api/v1/vehicle-rental/running-charts/{chart}/{kind}/charges/mileage`:

- `GET` returns a read-only quote with agreement reference/version, cycle, timezone, allowance application, rate, amount and pool head.
- `POST` requires policy acceptance, expected revisions/timezone/pool head and document inputs; returns HTTP 201 with `assessment` and nullable `invoice`.
- Review, reissue and void use the existing scoped usage-charge history/actions.

The chart charge screen has an **Assess mileage** view, shows backend pricing and policy before acceptance, clears stale quotes after conflicts and reports zero-cost completion without claiming an invoice exists.

See [the delivery record](../changes/2026-09-15-rental-mileage-assessment.md) for verification. This contract does not define whole-distance hire minimums, AC/driver-base tariffs, deposit disposition or downtime credits.
