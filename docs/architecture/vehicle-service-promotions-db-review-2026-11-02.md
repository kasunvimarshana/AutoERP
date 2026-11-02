# Vehicle Service Promotions database review

Date: 2026-11-02
Status: proposed schema, based on the current source; no migration or application code applied

## Decision

Existing operational and financial tables should be reused as sources and immutable billing records. They cannot hold reusable offer definitions, issued cards, per-subject progress, and benefit-use history without mixing responsibilities. Add Promotions-owned tables for those concepts and one Vehicle Service line amount column to keep promotional discounts distinct from manual discounts. Do not create a voucher table or encode offers as items, job types, invoice metadata, or customer/vehicle JSON.

## Existing tables to reuse

| Table | Existing responsibility | Promotions use |
| --- | --- | --- |
| `items`, `item_prices` | Catalog service identity and dated prices | Select qualifying/reward services; resolve normal current price. |
| `customers`, `vehicles` | Customer and vehicle identity | Offer subject and card holder/assigned vehicle. Registration number is display, not a foreign key. |
| `vehicle_service_jobs`, `vehicle_service_job_lines` | Job party, vehicle, item, quantity, price, manual line discount | Show/apply the offer to the actual service line. Keep manual discount fields as manual inputs. |
| `invoices`, `invoice_lines`, `invoice_source_lines`, `vehicle_service_invoice_links` | Posted billing amounts and exact job-line quantities | Count only posted eligible quantities; tie redemptions to posted invoice lines; reverse through the existing invoice lifecycle. |
| `vehicle_service_job_discounts` | Append-only whole-job manual discount revisions | Leave its entry and history unchanged. |

Voucher is a projection of Payment/Finance documents and has no offer/card persistence contract.

## Proposed new tables, all owned by Promotions

Each row carries `tenant_id`; business references use tenant-scoped composite foreign keys as the current schema does. Each table gets its own explicit Laravel migration. Published offer/card definitions are immutable; create a new definition for changed commercial terms instead of updating historical promises.

1. `promotion_offers`: stable code/name, qualifying `item_id`, paid quantity threshold, reward `item_id`, reward quantity, count scope (customer/vehicle), valid dates, optional branch, publication/status, `row_version`, created actor. One published row is one fixed buy-N-get-M rule. The customer/vehicle assignment is separate.
2. `promotion_card_templates`: code/name, start/end dates, optional branch, publication/status, `row_version`, created actor. One row defines a reusable free card design. It is never a sale item or payment source.
3. `promotion_card_benefits`: template FK and sequence, benefit kind (`direct` or `earned`). A direct benefit holds item FK, free/percentage effect, rate and allowed-use count. An earned benefit links an immutable `promotion_offer` and stores no duplicate threshold. Optional weekday eligibility belongs to the benefit. Validate the two shapes explicitly and keep published rows immutable.
4. `promotion_cards`: unique tenant card number, template FK, customer FK, current vehicle FK, issue date, effective end, status, `row_version`, issued actor. Its effective start is no earlier than issue date or template start. The template remains the source of its immutable promised terms. Uniqueness on `(tenant_id, template_id, customer_id, vehicle_id)` permits one issue of that dated template to a customer/vehicle pair. A replacement rotates the current card number on the same issued-card row, with the old/new numbers in a lifecycle event, so its usage is preserved. A later campaign uses a new template.
5. `promotion_offer_assignments`: offer FK, customer FK, optional vehicle FK, optional originating card FK, effective dates, status, `row_version`, assigned actor, and a non-null tenant-scoped subject key for uniqueness. Customer scope counts the service customer across their vehicles; vehicle scope counts the selected vehicle. A linked card creates the assignment once at issue; a second card cannot duplicate it.
6. `promotion_job_line_choices`: job-line FK and either the selected issued-card plus direct template benefit, or an earned offer assignment, with `row_version` and selected actor. This stores a pending staff choice without consuming a use. One active promotional choice per job line. No job/card FK is needed solely to remember a card picker state when no benefit was selected.
7. `promotion_qualification_events`: append-only offer-assignment FK, posted invoice-line FK, qualifying quantity, event type (qualification/correction), original-event FK for corrections, posting timestamp and rule/subject snapshots. Unique source-event identity prevents partial-invoice retries from counting the same posted quantity twice. Net quantity determines repeated buy-N-get-M progress and earned reward count.
8. `promotion_redemption_events`: append-only issued-card plus direct template-benefit FKs, or an earned-offer assignment FK, posted invoice-line FK, redeemed quantity, applied discount amount and original price/rule snapshots, event type (redeem/correction), original-event FK, actor and timestamp. Unique source-event identity and locked card/assignment rows prevent double use. Remaining direct uses and earned rewards derive from these events and qualification events.
9. `promotion_card_lifecycle_events`: append-only issue, transfer, suspend, replace, and reactivate actions with old/new vehicle, old/new card number where relevant, actor, reason, and timestamp. The current card row is a version-checked projection; historical assignment is never overwritten without an event.

Indexes should follow the actual reads: `(tenant_id, customer_id, status)`, `(tenant_id, vehicle_id, status)` for cards/assignments, `(tenant_id, promotion_offer_assignment_id, posted_at)` for qualifications, `(tenant_id, promotion_card_id, benefit_id)` or equivalent for redemptions, and unique tenant/card number and source-event keys. Do not add reverse relationships merely for convenience. Avoid nullable columns in unique business keys because their semantics vary by database.

## One targeted change to an existing table

Add `promotion_discount_amount DECIMAL(20,6) NOT NULL DEFAULT 0` to `vehicle_service_job_lines` via a portable additive migration. Existing `discount_amount` stays the manual line discount. Vehicle Service calculation then uses both amounts, caps the total at the eligible line price, recalculates tax and job totals, and preserves the current whole-job discount UI/record. `invoice_lines.discount_amount` remains the posted combined line-discount snapshot; `promotion_redemption_events` identifies its promotional portion and source. No new `invoice_lines` or `invoices` column is required for the first release.

This column alone is insufficient: it cannot identify the card, offer, customer progress, use limit, or reversal. Conversely, storing only a promotional event without a job-line amount would leave the current job totals/preview unaware of the offer.

## Transaction and integrity rules

- When a Service Job selects a card benefit, verify the card's tenant, customer, vehicle, dates, branch/weekday rule, and item. The job line must refer to an actual eligible catalog item; do not use raw text descriptions or a zero-price placeholder item.
- At invoice posting, lock the job plus the relevant card/assignment in a consistent order, re-evaluate eligibility against the saved job-line unit price and the quantity being invoiced, append qualification/redemption events, and post the invoice in one transaction. The item's current catalog price is used when staff first adds the service; later catalog price changes must not silently reprice an existing job. An unposted job only has a pending choice and preview; no card use or paid-count is consumed.
- A posted invoice may represent part of a job line. Count and redeem only that posted quantity through `invoice_source_lines`/`invoice_lines`; use invoice-line-based unique event keys, not a single job-level flag. A partly discounted service counts when its posted amount attributable to that service is above zero after line and allocated whole-job discounts. A fully free service does not count. Actual payment settlement is not required because qualification occurs at invoice posting.
- On invoice void/cancel/reversal, append inverse promotion events in the same restoration transaction. Preserve the original invoice and event snapshots. If a previously earned free use was already spent, retain the financial reversal, record a recoverable deficit, and block further reward use pending manager review.
- Keep all IDs tenant-scoped and restrict deletion of referenced published terms, cards, events, jobs, and invoices. No count or balance should be manually updated without an audit event. The UI returns structured customer, vehicle, item, card, and offer labels, not raw foreign keys.

## Migration approach

Create each new table in its own explicit migration in dependency order, followed by the single additive Vehicle Service line migration. Use Laravel's portable schema APIs and explicit columns, foreign keys, and indexes. The current base migrations are already part of deployed history, so do not rewrite them for this feature. No data backfill is needed: cards and offers start empty, old job lines get zero promotional discount, and past invoices do not qualify before assignment.

This is a schema design review, not a claim that current production data was inspected or migrated. Confirm branch/day eligibility details and replacement/transfer permissions before writing migrations.
