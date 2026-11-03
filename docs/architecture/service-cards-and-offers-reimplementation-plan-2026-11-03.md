# Service Cards & Offers: reimplementation plan

Date: 2026-11-03

## Verified starting point

- This checkout has no Promotion backend or frontend module. Vehicle Service already owns jobs, lines, inventory reservation, pricing, and invoice creation. Its invoice creator approves and posts within one transaction.
- Invoice has a source restoration handler used when a service invoice is cancelled or reversed. Audit has a recorder. Vehicle Service has a job item lookup supporting service, labour, combo, package, untracked stock, and batch options.
- The supplied `taprjfji_autoerp (17).sql` contains no `promotion` text. Before deployment, separately inspect the target database for promotion tables and data; do not infer its state from this backup.
- `AGENTS.md` and `RULES.md` are identical. Keep module ownership, tenant and branch integrity, atomic version checked writes, append-only transaction history, explicit one-table migrations, and guided relationship inputs.

## Confirmed product behavior

1. Count offers: configure paid item, reward item, paid units needed, free units earned, customer or vehicle scope, start date, and optional end date. Each completed threshold earns rewards again. Assignment is explicit per eligible customer or vehicle.
2. Paid, posted invoice units qualify, including paid units with a partial discount. Fully free or nonbillable lines do not qualify. Job creation, offer assignment, and payment alone do not add a count. Earlier invoices are not backfilled.
3. A physical promotional card design has a name, validity period, and item benefits: free or percentage discount with a limited number of uses and optional weekday restrictions. It may include count offers. Issue a card free to a customer and one vehicle. An expired card cannot redeem. Transfer requires authorized approval and audit.
4. One job line receives at most one promotion benefit; choose the greatest eligible promotion saving. Existing manual discount controls and permission flows remain intact. Promotion savings use the remaining price after manual line discount and must not make the line negative.
5. Management can pause/reactivate offers. Deletion is only for definitions with no assignments, card references, or financial history. Otherwise pause. An issued card or used offer is historical and is never erased.

## Ownership and integration

- Create a Promotion module for definitions, card issuance, enrollments, qualification and redemption ledgers, eligibility, and permission checks. Keep its business rules in this module.
- Vehicle Service retains job line creation, inventory handling, totals, and invoice mapping. Add only its promotion amount and narrow calls to Promotion validation/posting. Invoice retains status transitions and source restoration; invoke Promotion correction from the source restoration transaction.
- Register the Promotion module's routes, migrations, permissions, and provider using current module conventions. Do not put promotion rules in Invoice, Voucher, or React.

## Database design

Use portable Laravel migrations, one table per migration, tenant-scoped foreign keys and explicit indexes. Keep the existing item, customer, vehicle, job, invoice, and audit tables as sources of truth.

| Table/change | Purpose and key constraints |
| --- | --- |
| `promotion_offers` | Immutable published count rule: code, name, qualifying and reward item, threshold/reward units, scope, validity, status, version, tenant/branch. Unique tenant/code. |
| `promotion_card_templates` | Card design, dates, branch, status and version. Published benefits are immutable; revise by new design/version. |
| `promotion_card_benefits` | Ordered template benefits. Each row is either an item benefit with free/percent rule and use limit, or a referenced count offer. Enforce the exclusive shape in validation and database constraints where portable. |
| `promotion_cards` | Issued physical card number, template, customer, vehicle, issue/expiry, status and version. Unique card number per tenant. |
| `promotion_offer_assignments` | Enrollment of an offer for the selected subject, with optional source card and effective dates. Store customer and optional vehicle FKs. Transactionally lock the offer and reject overlapping active enrollments using those FKs; preserve prior enrollments for renewal history. |
| `promotion_job_line_choices` | Current, version checked benefit selection for an uninvoiced job line. FKs to line and card benefit or assignment, with one selection per line. |
| `promotion_qualification_events` | Append-only posted paid quantity, invoice line, assignment, charged amount snapshot, and correction link. Unique source/event type prevents duplicate posting. |
| `promotion_redemption_events` | Append-only redeemed quantity, invoice line, card benefit or assignment, unit price and discount snapshots, and correction link. |
| `promotion_offer_lifecycle_events` | Append-only create, status and delete history with offer identity/status snapshots and actor. |
| `promotion_card_lifecycle_events` | Append-only issue, pause, reactivate and transfer history with card/status/vehicle snapshots, actor and reason. |
| `vehicle_service_job_lines` alteration | Add a separate `promotion_discount_amount`; do not repurpose manual discount columns. Include it in totals and invoice line mapping. |

Use append-only offer and card lifecycle events for create, pause, issue, transfer and delete actions, recording actor, before/after state, reason where applicable, and time. Index assignments by tenant/offer/vehicle/status, events by tenant/assignment, and cards by tenant/customer/vehicle/status. Review every FK and deletion rule before migration; no circular ownership is needed.

## Backend workflow

1. Offer/card management: validate active catalog item and branch, dates, percentage/use limits, item support, and scope. A card design can include only vehicle-scoped count offers because the physical card belongs to one vehicle. Created definitions remain immutable; status changes and issuance use transactions and version checks.
2. Enrollment: after job customer/vehicle selection, return active eligible offers and already enrolled ones. An authorized user adds an offer once; backend verifies ownership, effective dates, branch, and duplicate/overlap under locks. Card-issued enrollments follow the card's vehicle.
3. Job line: show candidate benefits with item code and name. Add reward items through the existing Vehicle Service line path, including stock/batch checks and resolved price. Select a benefit for a matching line; Promotion computes the maximum permitted discount, then Vehicle Service recalculates its totals. Recheck eligibility and versions on each write.
4. Invoice: in the existing invoice transaction, after Posted transition, record qualification and redemption events from the invoice's actual source lines and final charged amounts, including allocated whole-job discount. Match the exact item ID, customer, vehicle, branch, assignment dates and status. Count invoice quantity, not the mere number of jobs. Persist snapshots; never rewrite posted history.
5. Cancellation/reversal: the existing restoration path appends equal and opposite correction events, restoring earned/used balances. Repeated posting or reversal must be idempotent. Lock benefit/enrollment rows so simultaneous invoices cannot overspend a free use.
6. Progress: derive totals from qualification and redemption events. Return paid progress, remaining units, available free units and human-readable related item/card objects. Scope every query by tenant and branch.

## UI flow

- Add **Service Cards & Offers** under Vehicle Service with focused tabs: **Count offers**, **Card designs**, **Issued cards**. Offer list shows status, period, item pair, Deactivate/Reactivate, and guarded Delete.
- Use the existing Vehicle Service item lookup and controlled customer/vehicle selectors. Opening an item selector displays supported items and typing filters them. Display item code and name together; never ask users for raw IDs.
- At the top of job create/detail, after customer and vehicle selection, show a compact strip: active card, `Added` enrolled offers, `N of 5 paid`, `M to next free`, `Free ready`, and a checkbox-like **Add offer** action for eligible unenrolled offers. State clearly that count updates after invoice posting.
- On each relevant job line show whether it will count, why it will not count (for example, different Paid item, free/nonbillable, or inactive offer), available savings, and a one-click benefit action. Show price and promotion savings before saving. Keep history and advanced details on a separate view.
- Display money and counts for humans (for example `1/5`, not `1.000000/5`). Surface permission, expiry, stock, version conflict, and item mismatch errors in place.

## Permissions

- `promotion.view`: read definitions, cards, availability and progress.
- `promotion.manage`: create/publish/pause/reactivate definitions, issue/transfer/revoke cards, delete unused drafts.
- `promotion.redeem`: enroll an eligible job customer/vehicle and select a job-line benefit. Route middleware and service checks enforce this; UI hides actions without permission.
- Keep existing Vehicle Service, Item, Inventory, Invoice, and manual discount permissions as additional gates where those operations are performed.

## Implementation order and acceptance gates

1. Inspect target DB schema/data and existing migration state. Choose a documented import path only if actual promotion records exist; do not blindly run old migrations or import the SQL dump.
2. Add schema, enums, permission registration and API resources. Test FK/unique constraints on MySQL and SQLite; use one table per migration.
3. Implement offer/card lifecycle and enrollment APIs with authorization, version conflicts, branch/tenant checks, and audit.
4. Integrate Vehicle Service pricing/line choices and invoice posting/reversal with append-only ledgers in the same transactions.
5. Build management and job UI, then verify keyboard/search behavior and clear statuses.
6. Test paid discounted qualification, free exclusion, exact item mismatch, customer/vehicle isolation, repeated 5-to-1 cycles, expiry, stockable item handling, concurrent redemption, duplicate posting, partial invoicing, reversal, and unchanged manual discount behavior. Run focused backend/frontend tests, typecheck and lint.
7. Deploy with backup, run migrations, assign roles, and smoke test one complete paid-to-free cycle in staging. Reconcile counted invoice quantities with the event ledger before enabling for clients. Add an append-only `/docs/changes` record after implementation.

## Deliberate scope boundaries

- No voucher-module coupling, automatic historical invoice backfill, or editing of posted financial events.
- Card artwork/printing is outside the transaction workflow. The system records the physical card number and benefits; printable artwork can be a later feature.
- If the target environment has legacy promotion records, migration and reconciliation need a separate evidence-based mapping before the new feature is enabled.
