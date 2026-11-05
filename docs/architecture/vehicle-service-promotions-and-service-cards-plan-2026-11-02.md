# Vehicle Service promotions and Service Cards: product plan

Date: 2026-11-02
Status: Planning proposal; no implementation authorized by this document

## Confirmed business decisions

- A qualifying service counts only after its invoice is posted.
- A partly discounted service qualifies when its posted amount attributable to that service remains above zero after discounts. A fully free service does not qualify. Payment settlement is not required.
- Each buy-N-get-M campaign chooses customer-wide or vehicle-specific counting.
- A Service Card is issued free of charge. The business may define multiple card templates and issue many cards from each template, with an explicit validity date range.
- A card may include direct benefits with fixed use limits and count-based benefits such as "5 Body Wash, then 1 free" or "5 Wash & Vacuum, then 1 free". The count threshold is entered in setup, not hardcoded.
- Count-based rewards repeat for every completed paid threshold during the valid period: 5 paid uses earn 1 free, 10 earn 2, and free redemptions never advance the paid count.
- A Service Card is issued to a customer and one vehicle. Transfer requires a controlled, audited manager action.
- At most one promotion applies to a service line. If several are eligible, show them and apply the one that gives the customer the greatest valid discount.
- Existing manual line and whole-job discount controls stay available and retain their current staff workflow. Promotional benefits are tracked separately and must not overwrite a manually entered discount.
- Buy-N-get-M programs can be assigned to a customer or a vehicle. The requested examples are customer-wide; progress starts from assignment and counts only qualifying invoices posted during the offer period.

## Current system fit

- Voucher currently presents receipt, payment, and finance journal documents. It does not own offers, entitlements, or customer reward balances. Do not add this feature to Voucher.
- Item owns the catalog entries for Body Wash, Wash & Vacuum, Oil Change, Rain Protection, and any eligible package. Vehicle Service job lines reference these items and already support line discounts, tax, and cost. A promotion references catalog items; it is not a job type and should not be represented by a zero-price item.
- Vehicle Service owns jobs, lines, billing preparation, and source-to-invoice mapping. Invoice owns posted financial documents and their reversal lifecycle. Customer and Vehicle remain the identity sources.
- Add a focused Promotions capability for campaign definitions, Service Card templates/issuance, entitlement accounting, and promotion history. Integrate it with Vehicle Service through explicit application services/events at invoice posting and reversal, without putting promotion rules in Voucher or Item.

## Product behavior

### Earned service offers

An administrator creates an offer such as "Buy 5 Body Wash, get 1 Body Wash free" or "Buy 5 Wash & Vacuum, get 1 free". The setup selects the qualifying catalog item, required paid quantity, reward catalog item, reward quantity, subject scope (customer or vehicle), active dates, and eligible branches. The rule may stand alone or be attached to a Service Card template. Staff assign a standalone published offer to a customer or vehicle through a searchable selector. Issuing a card automatically enrolls its linked count-based offers for that card's customer or vehicle; staff do not assign the same rule a second time. The assignment shows its effective date and current progress. The system previews the rule in plain language before publishing it. Avoid arbitrary rule scripting in the first release.

Each posted, eligible invoice quantity contributes once to the campaign's subject when the amount attributable to that service remains above zero after line and allocated whole-job discounts. A partly discounted service therefore counts; a fully free service does not. Counting occurs at invoice posting, regardless of later payment settlement. Each complete paid threshold grants its reward, so 10 qualifying washes in a buy-5-get-1 program grant two free washes during the valid period. An unused earned reward can be applied to a matching future job line. The fifth qualifying service and the free reward should be separate billing events by default so staff can explain the entitlement before using it. Partial invoices contribute only the quantity actually posted; the same source quantity cannot count twice. Reversing/voiding a qualifying invoice creates a compensating event and updates the available balance. If its earned reward was already redeemed, preserve the financial reversal, record a recoverable promotion deficit for manager review, and prevent further reward use until it is resolved. Never silently rewrite the redemption history.

### Physical Service Cards

An administrator defines as many reusable free card templates as needed. Each has a name, start/end dates, and benefit rows. A direct benefit selects a catalog item, free/percentage effect, and use limit, for example two free Body Wash uses or one 25% Oil Change use. A count-based row selects the qualifying item, paid count threshold, reward item, and free reward quantity, for example "Body Wash 5 → 1 free". This row references an immutable published offer owned by Promotions; do not duplicate the rule or progress ledger inside the card. A template may mix both kinds. Optional per-benefit weekday and branch restrictions represent conditions visible on the supplied sample card; the sample's printed prices and claimed saving are reference material, not system defaults. The issued card references its immutable direct-benefit terms and linked offer, has a unique human-readable card number, and is assigned to a customer plus one registered vehicle. Its effective start is the later of the template start and issue date; it ends no later than the template end. Card issue never creates an invoice or payment. The printed card/QR is an identifier, not proof of eligibility by itself; the server checks the active assignment and balance.

At a Vehicle Service job, staff selects the customer and vehicle; a compact strip near the top shows active card(s), earned-offer progress, and available rewards. Selecting a card previews its usable benefits and ordinary item prices. Staff selects the services actually requested and clicks one action to add the corresponding catalog items to Job Lines; existing matching lines are reused or clearly flagged instead of being duplicated. The preview shows the benefit against each line without hiding the ordinary price. A controlled transfer changes the current vehicle assignment with a reason, actor, and timestamp while preserving the original issuance and redemption history. Expiry, suspension, and replacement retain history. Card uses are consumed only when the related invoice is posted. A cancelled or voided invoice restores the use through a separate compensating event.

### Pricing and stacking

An applicable free service is represented as a normal service line with its ordinary price and a 100% promotion discount. Percentage offers discount only the eligible line quantity and amount. Vehicle Service calculates the source-line amount and tax using its existing calculation rules; the posted Invoice stores the resulting immutable discount and source references. Inventory, labor cost, and commission behavior remain visible and must be checked for free services before release.

The server computes all eligible offers for a line from current campaign/card balances. It selects the largest valid monetary saving and displays the alternatives. No two promotional offers stack on one line. Existing manual line and whole-job discount entry remains untouched: promotions use a distinct amount and reason/source in the calculation and invoice snapshot. Calculate the promotional saving against the amount remaining after a manual line discount; apply the current whole-job discount to the remaining job base. Cap the combined reduction at the eligible amount and show the breakdown before posting. Revalidate eligibility and recompute the saving from the saved job-line price at invoice posting, so a stale preview cannot consume a benefit or over-discount a line; later catalog price changes do not reprice existing jobs. The exact tax and commission result needs focused integration tests before release.

## Ownership and data model

- Promotion offer: tenant-owned definition with lifecycle and an immutable published rule. Keep item references, scope, threshold, reward quantity, dates, and branch eligibility explicit. Changes to a published rule create a new definition; existing activity remains linked to the original.
- Offer assignment: tenant-owned customer or vehicle enrollment with effective date, status, actor, and version. It may come from a standalone assignment or a card issue, but the same assignment and progress model is used. The counting subject is fixed at assignment; qualification before that date is excluded unless an explicit migration rule is approved.
- Earn activity: append-only qualifying events keyed uniquely to a posted invoice line and immutable offer definition, plus compensating reversal events. Derived progress is not the historical source of truth.
- Reward redemption: append-only events identifying offer, subject, item, source invoice/job line, actor, and rule snapshot. Earned reward count derives from net qualifying quantity and the fixed threshold; available balance subtracts redemptions. It cannot be directly edited.
- Card template and benefit: tenant-owned reusable definitions with explicit date ranges. Direct benefits hold free/discount value and a use limit; earned benefits reference an immutable published offer. Published templates/benefits remain unchanged; a revised template is a new definition. Issued cards reference those immutable terms and redemption events snapshot the applied amounts. Many cards can be issued from one template, and one customer may hold several different cards; the job shows only valid cards for its selected customer and vehicle. Allow only one active issue of a given template for the same customer/vehicle entitlement scope. Replacing a lost card preserves spent uses and earned progress rather than granting a fresh allowance. A second card linked to the same offer cannot create a duplicate enrollment or count the same invoice twice.
- Card assignment and redemption history: append-only issue, transfer, suspend, replace, redeem, and reversal events with reason and actor where relevant. The current card assignment/version is a guarded projection for fast reads.
- Every foreign key is tenant-scoped; branch eligibility is explicit. Customer-wide counting uses the job's service customer, not the optional bill-to customer. Vehicle-wide counting uses the job vehicle. A card must match both its assigned customer and current vehicle unless a controlled transfer has occurred. The display uses customer names, registration numbers, item names, and card numbers, never raw IDs.
- Review all proposed foreign keys before implementation. The promotion-owned records need one-way references to Customer, Vehicle, Item, Vehicle Service source lines, and Invoice; no reverse relationship is required merely for convenience.

## Write and reversal guarantees

Eligibility evaluation, balance consumption, invoice posting, and immutable history entry must be one transaction. Lock the relevant campaign-subject or issued-card balance in a consistent order, require expected versions for staff-initiated mutations, and use unique source-event keys to make retries idempotent. Competing redemptions must yield one success and a clear conflict response. Reversal handlers append compensating records rather than editing past events. Never rely on frontend counts for authorization or price calculation.

## Focused UI

1. Vehicle Service > Offers: list active/upcoming/ended programs; create a buy-N-get-M offer with guided item selection, period, scope, and branch rules; preview; publish; assign to a customer or vehicle; see progress and usage.
2. Vehicle Service > Service Cards: create multiple templates with name and date range. Each benefit row chooses either "Free/discount now" (item, value, use limit) or "Buy N, get M" (qualifying item, paid count, reward item/quantity, customer/vehicle count scope). Offer rules created through this form appear in Offers and use the same rule ledger. Issue/search free cards by customer or registration; show benefits, remaining uses/progress, expiry, and history; print/reprint; controlled transfer/suspend/replace.
3. Service Job > compact Offers & Card strip: appears after customer and vehicle selection, for example "Body Wash 3/5 — 2 more to free" and "Service Card: 2 benefits available". Selecting a card reveals benefit rows with "Add service"; adding places the actual item and normal price in Job Lines with a promotion preview. A ready free reward is shown here too. Keep full history and reporting out of the primary job form.
4. Job Lines and Invoice preview: show normal price, manual discount (if entered), promotion, final line amount, and projected uses. On posting conflict, refresh and explain what changed.
5. Customer and Vehicle details: read-only offer progress and active-card summary with a link to the full Promotions workspace.

## Delivery order and acceptance

1. Confirm remaining commercial rules: card replacement and transfer permissions, eligibility of packages, and treatment of refunds after reward use. Decide these before migration design. Cards are complimentary and buy-N-get-M rewards repeat for every completed paid threshold; default planning scope excludes invoices before offer assignment and card issuance.
2. Implement campaign definitions, published revisions, earned activity, and balance queries within Promotions. Add backend tests for customer/vehicle scope, tenant/branch isolation, partial invoices, duplicate events, concurrent posting, and reversals.
3. Connect Vehicle Service invoice preview/posting and Invoice reversal through explicit contracts. Test discount/tax/source snapshots, multiple eligible offers, stale versions, and inability to double redeem.
4. Add the focused Offers UI, then card templates, issuance, printing, transfer, limited uses, and Service Cards UI. Test staff flows with actual Body Wash, Wash & Vacuum, Oil Change, and Rain Protection catalog items.
5. Add redemption/progress reports and reconcile posted invoice totals, promotion events, card balances, and reversals. Roll out behind the tenant feature entitlement after migration and role setup. Do not backfill historical invoices without an approved rule and reconciliation plan.

Success means a customer or vehicle's fifth paid qualifying service earns exactly one reward, a sixth eligible service can consume it once, an issued card cannot exceed any benefit's use limit, two staff members cannot redeem the same remaining use, and invoice cancellation produces an auditable correction without rewriting history.
