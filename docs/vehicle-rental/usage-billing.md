# Running Chart OT and night-out billing

## Evidence and policy

TACGL/video observations V02 and V04 show distinct calculation/issuance actions, shared physical evidence and separate commercial sides. E06 in the knowledge base reconciles 24 hours 30 minutes at 500 to 12,250; E07 uses decimal-hour notation. These observations support explicitly typed minutes, not a universal parser for historical dotted text. OT and night-out fields and independent agreement rates are present in the fresh operational model.

The explicitly accepted implementation policy is `recorded_minutes_and_nights_v1`. It prices a finalized, recorded component; it does not infer overtime eligibility from clock time, impose a statutory workday, determine night-out qualification, or add an automatic multiplier to a separately agreed category rate. Finalization remains the control for recording the actual qualifying quantities. This policy is a documented new implementation convention, not a claim that every TACGL transaction used this algorithm.

| Component | Recorded quantity | Assigned agreement rate | Amount before Tax |
|---|---|---|---|
| Normal OT | `normal_ot_minutes` | `normal_ot_rate` per hour | minutes × rate ÷ 60 |
| Double OT | `double_ot_minutes` | `double_ot_rate` per hour | minutes × rate ÷ 60 |
| Triple OT | `triple_ot_minutes` | `triple_ot_rate` per hour | minutes × rate ÷ 60 |
| Night-outs | `night_outs` | `night_out_rate` per night | count × rate |

Rates come from the exact customer or owner agreement history revision selected by the vehicle use. Neither side borrows the other side's rate or consumption. Multiplication precedes division, followed by six-decimal truncation using the existing persisted money scale. One minute at 100/hour yields `1.666666`; 1,470 minutes at 500/hour yields `12250.000000`. The double/triple labels do not multiply the category rate again.

Unknown quantity or rate stays unknown. Zero remains distinguishable and produces a zero preview, but no financial document or source consumption. Monetary overflow is rejected before persistence. Client-supplied prices, quantities and totals are ignored. A read-only quote shows quantity, rate, denominator, amount or a reason billing is unavailable. Creation always recomputes under locks.

## Financial and correction workflow

1. Select a chart through the Running Chart register or assigned vehicle's chart list. Choose customer charges or owner payables, subject to permissions; owner payables require a linked owner agreement.
2. Review the assigned agreement reference and server-calculated component quote. Enter document date, optional due date and explicit accounting exchange rate, and accept the stated policy.
3. Create an immutable charge and canonical Invoice draft in one transaction. Invoice owns tax snapshots, posting plans, approval, posting and payment integration. The source is one indivisible assessed charge; its exact operational quantity/rate/denominator are preserved in the calculation and readable invoice description. No fractional minute-to-hour invoice allocation can introduce another rounding loss.
4. Review linked Invoice numbers and states. Cancel/reverse in Invoice as appropriate. Reissue uses the same immutable source amount and description with new document inputs. A second live invoice is prevented by Invoice's surviving source-quantity guard.
5. To correct the charge, first release every linked invoice, then void with expected charge revision, reason, actor and timestamp. Preserve the original amount and calculation. The same component can then receive a replacement charge; a voided charge cannot be reissued.
6. To correct physical evidence, first void every surviving customer **and** owner usage charge after releasing its invoices. Only then reverse the chart and create its governed correction. Cancelling an invoice alone does not release the chart's commercial assessment. This prevents physical correction from silently invalidating a surviving charge.

Each chart/component can have at most one non-voided charge per side. Components can be billed independently. This is not periodic mileage settlement, a deposit ledger, driver base-pay recovery, AC pricing, downtime credit or replacement surcharge; those components cannot be inferred from these OT/night-out records.

## Scope, locks and schema

Every request enforces authenticated tenant/organization scope, Rental and Invoice feature entitlement, the side's billing and agreement-view permissions, and chart-view permission. Chart and agreement expected revisions are required for writes. Only finalized charts and active/closed agreements support creation/reissue. Missing linked owner agreement, foreign IDs, invalid policy/component, stale state and unresolved values fail explicitly.

Write lock order is physical vehicle, agreement, chart and charge. Chart reversal holds the same physical vehicle mutex before checking both commercial consumers. Duplicate creation, reissue, void and evidence reversal therefore share the relevant source locks. The complete Tax/Invoice operation stays within the outer transaction; downstream failure rolls back the charge. SQLite tests establish atomic state behavior, not InnoDB contention guarantees.

Separate customer/owner usage-charge tables have mandatory tenant-composite references to the side's agreement, chart, organization and actors. The agreement reference freezes the commercial identity and supports database integrity; chart identity supports source-consumption lookup. The immutable snapshot stores the selected agreement revision and chart revision. No nullable customer/owner XOR columns, bidirectional inverse charge pointers or duplicated Invoice foreign key are added. Non-unique chart/component indexes allow retained void history; the locked non-voided check enforces active uniqueness.

`RentalCharge` owns the shared immutable calculation/void model contract. `RentalChargeDocuments` centralizes document validation, Invoice/Tax handoff and release-before-void controls for both base and usage billing. Base pricing and chart pricing stay in their own services. The extracted shared code comes from the current fresh module; no removed Rental implementation is used.

## API

Under `/api/v1/vehicle-rental/running-charts/{chart}/{kind}/charges`, where kind is `customer` or `owner`:

- `GET /`: scoped agreement version/reference, currency, component quotes and paginated charge/document history.
- `POST /`: component, policy, expected chart/agreement versions and invoice inputs; returns the created Invoice resource with HTTP 201.
- `POST /{charge}/reissue`: expected chart/agreement versions and invoice inputs; returns the replacement Invoice resource.
- `POST /{charge}/void`: expected chart/agreement/charge versions and nonblank reason; HTTP 204.

The backend derives agreement relationships from the selected chart; users never type foreign-key identifiers. A stale result must be reloaded before retrying. Raw source IDs are not payment/accounting instructions.

## Verification

Service and authenticated HTTP coverage checks independent rates, all four categories, exact minute arithmetic, read-only quotes, unknown/zero handling, overflow, policy rejection, duplicate prevention, revision conflicts, tenant/permission/entitlement boundaries, immutable history, release/void/reissue, chart-reversal guards and rollback on incomplete Tax configuration. Both commercial sides execute Invoice approval, Finance posting, reversal and reissue with test-only account mappings. Frontend tests cover explicit acceptance, server quotes, unknown-value prevention, conflicts and governed correction actions.

See the append-only [delivery record](../changes/2026-09-12-rental-usage-billing.md) for suite and migration results. Production-data migration, real database contention and human acceptance require their own execution evidence.
