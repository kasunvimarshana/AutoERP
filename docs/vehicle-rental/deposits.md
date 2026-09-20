# Customer security deposits

Implemented against `f0ec8e6fff0eee82351367b06673ec604f7865dc`. This is a documented implementation decision using the current immutable agreement and Payment contracts, not a claim that historical TACGL specified every disposition rule.

## Source and meaning

A customer agreement's explicit `deposit_requirement` is refundable security. Null means unknown; zero means no collection requirement. Neither creates cash, an invoice, a reservation hold or revenue. Only an active agreement with a positive requirement can initiate a new receipt. Closing an agreement prevents new collection but does not erase money or prevent authorized Payment disposition. Physical return/closure does not automatically refund, forfeit or invoice security.

[Malkey's chauffeur terms](https://www.malkey.lk/rates/with-driver-rates/) separately identify rent and a refundable deposit. [IRD's VAT consolidation, section 5(8)](https://www.ird.gov.lk/en/publications/Value%20Added%20Tax_Acts/VAT_Act_No_14%5BE%5D_2002_(Consolidation_2025).pdf) distinguishes refundable money in deposit-based supply valuation. Rechecked 2026-09-20. These support preserving refundability, not importing that operator's rates or authorizing arbitrary forfeiture. The receipt creates a liability through the configured Finance `rental_deposit` profile; no sales tax, revenue invoice or withholding is inferred from receiving refundable security. Tax on an underlying charge remains Invoice/Tax's responsibility.

## Collection and authority

`GET/POST /api/v1/vehicle-rental/customer/agreements/{agreement}/deposits` requires Rental and Payment entitlements. Both require customer-agreement view permission; read additionally requires `payments.view`, creation `payments.create`. Generic Payment requests cannot supply source identity. The controlled Rental command derives customer, currency, tenant, organization and source from the scoped agreement. Receipt date, exchange rate and configured payment-method lines are entered explicitly; instrument validations remain Payment-owned.

The new source discriminator `vehicle_rental_customer_agreement_deposit` identifies this fresh agreement. It is distinct from existing historical Payment source identities. Immutable metadata captures agreement reference, revision and requirement. No removed Rental implementation or data schema was restored.

Partial receipts are allowed. Under the agreement mutex, creation rejects net receipts exceeding the explicit requirement. Net receipts for this gate are the sum of each non-voided/non-reversed source payment's original total less its active refunded amount. Draft, submitted, rejected-but-resubmittable and approved receipts all reserve collection capacity. Invoice application does not release collection capacity: applying security must not silently authorize collecting it twice. A refund releases capacity for an explicitly initiated subsequent receipt; there is no automatic replenishment.

The command version-checks the agreement and requires a Payment idempotency key. Payment creates/replays the receipt before the capacity check within the same enclosing transaction. Therefore an exact retry at the limit returns the same receipt; a different new request exceeding capacity rolls back its payment, lines, number reservation and idempotency record. Payload changes under one key fail. The UI retains the key after an uncertain failure and retires it only after confirmed success.

## Disposition and corrections

| Action | Owner and effect |
| --- | --- |
| Submit, approve, post | Payment's existing separate permissions and revision guards; Finance resolves configured cash/bank and customer-deposit accounts. A draft is not posted cash. |
| Apply | Explicit Payment invoice allocation; same tenant, organization, party and currency; payable invoice and available balance required. Finance moves deposit liability to receivable settlement. No automatic FIFO or implied consent is introduced by Rental. |
| Refund | Explicit authorized Payment refund with reason; approved/posted original, available unapplied amount and valid instrument required. Payment creates and posts the opposite-direction refund atomically, preserves lineage and updates its balance. |
| Void unposted receipt | Payment preserves the original receipt and lifecycle history; its capacity is released. |
| Reverse posted receipt | Payment reverses Finance and invoice allocations. Active refunds must first be reversed. Released documents cannot be resurrected. |
| Reverse refund | Payment reverses the refund and restores the original's liability/balance. It preserves the historical receipt; no Rental balance is manually rewritten. |
| Forfeiture/damage deduction | No direct liability-to-revenue shortcut. An independently justified, approved underlying Invoice must exist before explicit allocation. This feature does not establish contractual entitlement to damage or forfeiture. |

A later refund reversal can restore net security above the original requirement after a replacement receipt. This is an accounting correction, not permission for new collection: the summary shows the signed difference and further collection is blocked. Review the excess in Payment; never rewrite or suppress its history merely to make the requirement match. Collection capacity is a receipt-admission rule, not a rule that historical corrections may discard cash.

Payment's application currently permits explicit allocation to eligible invoices of the same customer/scope/currency, as its owner contract specifies. It does not infer that every same-customer debt is contractually deductible. Operators must choose the justified invoice; Rental does not auto-allocate on return. The existing instrument lifecycle and Finance period/configuration checks still apply.

## Relationships and operations

The activated customer agreement already owns an immutable requirement and cannot be deleted. Payment already owns source identity, receipt lines, allocations, refunds, reversals and lifecycle events. Reuse those directed references through a Payment-owned scoped source query. Adding a second deposit table, inverse agreement/payment FK or independently mutable Rental cash balance would duplicate ownership without adding a business fact. No migration or schema modification is needed for this capability. Existing Payment source index supports lookup.

The customer agreement review exposes an optional deposit panel with human-readable payment links, method controls, received/applied/refunded/unapplied values and explicit document/posting states. Amounts on voided/reversed rows are history, not available money. Refresh after work in Payment. The read summary is informational; every creation rechecks capacity under the agreement lock. Payment allocations/refunds/reversals retain their own payment locks and version checks; no cross-module reverse dependency is added.

Authenticated API tests cover receipt/retry/rollback, unknown/zero/draft rejection, stale agreement, source-field spoof rejection, authorization, entitlements, tenant isolation, partial receipts, posting, invoice application, partial refund, stale refund, refund reversal, receipt reversal and void/replacement. Frontend tests cover uncertain retry identity, over-collection prevention, guided methods, payment navigation and read-only access. Test ledger accounts are synthetic fixtures, not production configuration.
