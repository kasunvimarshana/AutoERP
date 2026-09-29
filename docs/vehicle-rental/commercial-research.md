# Vehicle Rental commercial and release research

Research date: **2026-09-10**. Engineering baseline: `bfe1f058c9861a21dee0ee148ea231e4aa104d9e` on `worktree-0.0.8`.

## Finding and scope

The evidence supports independent customer revenue and owner cost, physical usage history, explicit commercial terms, and governed financial corrections. It does **not** support one industry-wide monthly divisor, mileage allowance, replacement tariff, downtime credit or deposit-forfeiture rule. Public operators offer materially different products. The appropriate design is agreement-specific, effective-dated policy with reproducible calculation inputs. A vendor example establishes that a policy exists; it does not establish that TACGL contracted for it.

The user's expanded research authorization permits defensible external derivations. It does not permit mislabeling those derivations as observed TACGL behavior. Distinguish four classes in implementation and review:

1. **Project fact:** record, screen or tested behavior with a project evidence identifier.
2. **Legal requirement:** dated authoritative provision, jurisdiction and established applicability.
3. **External commercial example:** operator, product, country and accessed date; usable as a supported design option.
4. **Engineering derivation:** an explicitly reasoned integrity control, not a new customer entitlement or charge.

This research supplements [the knowledge base](../knowledgebase.md), especially its E01–E16 evidence and VR-U01–VR-U29 uncertainty register. It is a targeted primary-source review, not an exhaustive legal opinion or a completed continuous audiovisual audit. No paid service, old removed Rental implementation, generated transcript or inaccessible encrypted content supplied evidence for these conclusions.

## Primary-source findings

All web sources below were accessed on 2026-09-10. Undated commercial pages are current observations, not historical TACGL contracts.

| ID | Source and applicable scope | Finding used here |
|---|---|---|
| CR01 | [Malkey self-drive rate table](https://www.malkey.lk/rates/self-drive-rates/), Sri Lankan operator/product example | Separate monthly and weekly prices and excess-mileage columns. Published prices are not AutoERP defaults. The final retrieved page did not reproduce the calendar-day wording seen in an earlier retrieval; that wording is not relied upon as a stable self-drive rule. |
| CR02 | [Malkey with-driver rates, terms below the table](https://www.malkey.lk/rates/with-driver-rates/), Sri Lankan chauffeur product | Mileage accumulates over consecutive calendar days, with 300 km over three days as an example. Origin/end measurement is the operator's office. Rental and refundable deposit are distinct; quote inclusions affect extras. |
| CR03 | [Malkey general terms](https://www.malkey.lk/terms-conditions/), that operator's contracts | Payment, fuel, late return, cancellation and refund conditions depend on the booking/quote. These terms do not prove TACGL's customer or supplier entitlement. |
| CR04 | [Europcar mileage FAQ](https://www.europcar.com/en-us/faq?question=is-there-mileage-limit-for-my-rental), country-dependent product terms | Limited and unlimited mileage products exist; its FAQ specifies limited mileage for contracts of at least 28 days and directs users to the booked rate/country terms. |
| CR05 | [Enterprise long-term rental](https://www.enterprise.com/en/car-rental/long-term.html), US product | Most vehicle classes have unlimited mileage. Long-term rental and its subscription product have different period rules. This is not a Sri Lankan legal standard. |
| CR06 | [Enterprise late-return FAQ](https://www.enterprise.com/en/car-rental-faqs/us-reservations/late-returns-policy.html), US reservations | Daily grace, hourly/additional-day charging and after-hours responsibility have specific conditions. No such thresholds are imported into Rental. |
| CR07 | [Stripe proration documentation](https://docs.stripe.com/billing/subscriptions/prorations), subscription billing example | Period boundaries, price changes, credit lineage and unpaid invoices affect proration. Its usage-billing treatment is a product choice, not a Rental standard. |
| CR08 | [MySQL 8.4 locking reads](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html), technical implementation authority | Locking reads require a transaction; locks end on commit/rollback. Locking an outer query does not automatically lock separately read nested tables. |
| CR09 | [IRD VAT overview](https://www.ird.gov.lk/en/Type%20of%20Taxes/SitePages/Value%20Added%20Tax%20(VAT).aspx?menuid=1204), Sri Lanka | Lists standard VAT at 18% from January 2024, registration conditions, exemptions and separate financial-services treatment. Links to later legislation must be checked before relying on summary text. |
| CR10 | [IRD VAT consolidation through 2025](https://www.ird.gov.lk/en/publications/Value%20Added%20Tax_Acts/VAT_Act_No_14%5BE%5D_2002_%28Consolidation_2025%29.pdf), relevant statutory provisions | Section 5(8) distinguishes refundable amounts in deposit-based supply valuation. First Schedule Part II public-passenger-transport exemption excludes specified services including tourists/excursions/taxis. Chapter IIIA concerns financial services. |
| CR11 | [VAT Amendment Act 14 of 2026](https://www.ird.gov.lk/en/publications/Value%20Added%20Tax_Acts/VAT_Act_No_14-2026_E.PDF), certified 30 June 2026 | Section 7 amends section 25C, including 20.5% from July 2026 in that financial-services regime. Section 8 introduces digital-services provisions. Neither establishes a universal Rental VAT rate of 20.5%. |
| CR12 | [IRD tax chart 2025/26](https://www.ird.gov.lk/en/publications/SitePages/tax_chart_2526.aspx?menuid=1404), explicitly that assessment-year summary | Resident rent above LKR 100,000 aggregate per calendar month: 10% of the full payment. Nonresident rent: 14%, subject to treaty provisions. Classification, exceptions and later legislation still matter. |
| CR13 | [Inland Revenue Act consolidation to March 2025](https://www.ird.gov.lk/en/publications/Acts_Income%20Tax_2017/IRA_Cons_Act_-_2025_Changes.pdf), sections 84/84A/195 and First Schedule paragraph 10 | Rent includes use/right to use property of any kind and ancillary assistance; service fee excludes rent. The current displayed rent threshold uses strictly greater than 100,000; an older adjacent provision includes equality. Effective-date annotations must be read. |
| CR14 | [Inland Revenue Amendment Act 11 of 2026](https://www.ird.gov.lk/en/publications/Acts_Income%20Tax_2017/IR_Act_No_11-2026_E.pdf), certified 3 June 2026 | Sections 12–15 amend withholding exceptions, service descriptions and reporting/certificates. Section 33 does not amend the rent definition; section 36 does not amend First Schedule paragraph 10. The new resident interest self-declaration exception is not a general Rental exemption. |
| CR15 | [IRD SSCL overview](https://www.ird.gov.lk/en/type%20of%20taxes/sitepages/social%20security%20contribution%20levy%20(sscl).aspx?menuid=1207), summary last updated January 2026 | Describes accrual-based liable turnover, thresholds, exclusions and different supply categories. It does not by itself authorize charging a customer an extra Rental invoice component. |

## Commercial decisions and implementation contracts

The following are design conclusions. Except for the Tax corrections described below, they are **not a claim that commercial runtime functionality has been delivered**.

### 1. Periods and proration — VR-U01/02/28

Project examples include agreement cycles that cross calendar months and a September partial-period amount. They do not distinguish a fixed-30 convention from a calendar/cycle-length convention. CR01–CR07 establish product variation, not a universal divisor.

Keep these concepts separate: physical custody interval; agreed charge interval; invoice period; commercial cycle; document date; payment date; tax effective date. An executing date remains a captured project fact and is not automatically a billing anchor.

For any proportional policy, the mathematical contract is `period rate × eligible quantity / denominator`, followed by the owning monetary rounding policy. Both quantities must have the same unit. This expression alone does not select the denominator, qualify eligibility or authorize a refund.

An implementable agreement policy needs its cycle anchor, named period convention, timezone, partial-period treatment and effective interval. Daily calendar charging, elapsed-time charging and whole-cycle minimums must not silently substitute for one another. A fixed denominator, if contracted, is a validated agreement term, never a literal hidden inside a service. Annual holidays, breaks and working days require their own evidence; weekday labels cannot supply them.

Acceptance cases: leap/non-leap February, 31-day month, non-calendar anniversary cycle, exact midnight boundary, partial first/last cycle, backdated amendment, rate change inside a period, and reversal of a previously invoiced partial interval. Split at actual policy boundaries, not arbitrary UI page boundaries. A repeated preview must not create new money or consume evidence.

### 2. Included KM and excess — VR-U03/06/23/25

CR02 supplies a concrete consecutive-day pooling example. CR04/CR05 prove that limited and unlimited offers coexist. Therefore `unknown`, `limited with zero allowance`, `limited with a positive allowance` and `unlimited` are distinct states. A null or zero source field cannot choose between them.

For a confirmed limited pool, excess is `max(eligible distance − allowed distance, zero)`; the charge uses the independently agreed excess rate. This arithmetic requires a defined pool identity, period, eligible distance basis and allowance accrual rule. Whole-distance hire is a different component and must never reuse excess-distance semantics merely because the unit is KM.

Pooling by chart would overcharge a contract that pools by cycle. Pooling by customer across unrelated agreements would grant an uncontracted concession. Replacement vehicles cannot each receive a new allowance unless the agreement explicitly establishes that result. Unused allowance has no monetary credit or carry-forward by implication.

Preserve garage/commercial/odometer observations independently. Missing odometer readings do not mean zero distance. A charge run spanning a chart boundary must use proven segment quantities; proportional splitting of total KM by elapsed time invents travel evidence.

### 3. Replacement and downtime — VR-U04/05/07

The existing atomic replacement records a physical exchange and preserves predecessor lineage. That exchange does not settle who caused a breakdown, what service remained available, or which party bears cost. No reviewed source establishes one universal customer/owner replacement tariff or downtime percentage.

A commercial decision requires: affected agreement side, original/replacement use, actual exchange time, applicable policy revision, eligible interruption interval, reason and supporting evidence. Customer service may continue while an original owner vehicle is unavailable; customer charge and original-owner deduction therefore cannot be calculated from each other's totals.

Union overlapping eligible interruption intervals before determining a time deduction. Do not deduct the same incident once through a replacement line and again through an off-road line. A service job is evidence of availability, not automatic proof of a contractual refund. Rejection of a deduction must not erase the incident.

A correction should identify its original commercial component and reason. CR07 illustrates why credit lineage and unpaid-document state matter; it does not prescribe TACGL's credit amount. Financial corrections must use Invoice/Payment/Finance ownership and closed-period rules rather than mutating prior snapshots.

### 4. Deposits and disposition — VR-U08/09

The evidence and CR02/CR03 distinguish rental charges from refundable security. CR10 also makes refundability relevant to valuation. A deposit requirement, received money and card authorization are separate facts; a captured requirement is not proof of a receipt.

Recommended ownership: Rental identifies the agreement and authorized disposition context; Payment owns receipt/refund/allocation/instrument facts; Tax owns taxable classification; Finance owns liability/revenue/cash posting. Do not add a second Rental bank ledger or update payment balances directly.

Each received amount must reconcile to the sum of the remaining refundable amount, governed applications, refunds and any separately authorized forfeiture, accounting for reversals. Currency and party must match the governed transaction. Competing refund/application requests must lock the same available balance and cannot each spend it. A reversed cheque cannot continue to fund a refundable balance.

Neither overdue return nor agreement closure universally authorizes forfeiture. Disposition needs an explicit contractual basis, amount, actor and evidence. Applying security to an invoice is not a second revenue event. Refund timing, charge priority and damage/tax treatment remain agreement/jurisdiction decisions; no first-in-first-out or automatic forfeiture default is justified here.

### 5. Tax and withholding — VR-U10/11/12

Jurisdiction must follow the supplier/customer transaction and registration facts, not the application locale. Historical TACGL VAT/NBT captions are not current tax configuration. Current Tax supports dated rates, scoped groups, party profiles and immutable posted snapshots; Rental should send semantic component context through those interfaces.

The Sri Lankan sources establish conditional tax requirements. They do not establish every AutoERP tenant's registration, treaty position, exemption or component classification. Public-passenger transport and a vehicle hire must not be equated from a vehicle type or a With Driver flag. Refundability must be retained when classifying a deposit. CR09–CR11 require separating ordinary supply VAT from financial-services VAT.

For rent withholding, CR12–CR14 support retaining residency, source classification, payment period and aggregate party payments. The 2026 amendments reviewed do not replace the resident-rent rate paragraph. This is a scoped reading, not verification of every later ruling or the taxpayer's applicability. A payment at the threshold differs from one above it; the earlier equality wording in the consolidation is historical. Configuration must retain legal source and effective date.

Engineering implication: threshold evaluation belongs to a Tax/Payment transaction across all relevant payments to the same withholdee for the statutory period, not separately to each Rental invoice, branch or vehicle. A split payment cannot reset an aggregate. Assessment must account for already withheld amounts and governed reversals. Do not independently withhold again at invoice and payment stages. A resident-interest declaration cannot be used to exempt rent.

SSCL liability and contractual pass-through are different questions. An accrual turnover levy cannot simply be implemented as a cash-payment surcharge. CR15 alone is insufficient to certify a complete current SSCL determination policy.

No statutory percentage, threshold, exemption, tax code or account number was hardcoded by this change. Arithmetic tests use synthetic rates. Decimal precision and tax rounding are distinct: exact six-decimal calculations do not prove a statutory invoice rounding convention.

### 6. Financial creation and source consumption

Project evidence requires independent customer and owner calculations from the same finalized usage. Each must retain side, agreement revision, period, source chart/use revisions, component purpose, typed quantities, rates, formula/policy identity, rounding result and downstream document reference.

Rental owns eligibility and calculation provenance. Invoice owns sales/payable documents, lifecycle and balance. Payment owns settlement. Finance owns journals and posting periods. Company-owned supply must not fabricate an external owner payable. Credit, receipt, allocation and bank reconciliation are separate lifecycle events.

Same-side consumption must be unique at the selected commercial unit. If consumption can be partial, a simple unique chart ID cannot prevent overlapping period/component consumption; coverage must also be enforced. Customer consumption must not block owner consumption. Source reversal after financial use requires downstream correction; deleting consumption to make a chart reusable would lose financial lineage.

An idempotency key must bind the actor's command to canonical request content. A retry with the same content returns the original outcome; a changed request with the same key conflicts. Calculation, consumption and local financial creation must be atomic, or use a deliberate durable handoff protocol if owner contracts cannot share a transaction. No network call should be assumed rollback-safe.

### 7. Concurrency and relationships

CR08 supports transaction-scoped locking. Existing operational commands coordinate through physical Vehicle locks and optimistic aggregate versions. SQLite success does not verify InnoDB contention behavior.

Review matrix for new financial work:

| Competing actions | Shared invariant and required verification |
|---|---|
| Two bookings / workshop admission | One physical vehicle's availability cannot be admitted twice for conflicting intervals |
| Two billings on the same side | At most one effective consumption of the same eligible commercial source |
| Customer and owner billing | Independent financial sides may both consume the same physical evidence |
| Finalize / reverse / bill a chart | Financial snapshot and source state cannot disagree at commit |
| Refund / allocate / forfeit security | Available received balance cannot be spent twice |
| Two threshold-crossing payments | Statutory period/party aggregate and withheld amount stay consistent |
| Amendment / calculation | Calculation retains one applicable immutable policy revision |

Use stable lock ordering and database uniqueness/FKs as appropriate. Retrying after deadlock must rerun the complete command and recheck policy/source state. Do not treat an advisory lookup as a reservation.

Existing customer-agreement and owner-agreement relationships represent different counterparties and obligations and must remain separate. The directed replacement predecessor records chronology and must remain. The Tax changes below need no new relationship, inverse link, table or dependency.

## Verified implementation findings in this change

Review of `TaxCalculationService` found four defects reproduced before correction:

| Defect | Observable result before correction | Corrected behavior |
|---|---|---|
| Malformed DTO line silently skipped | A submitted amount could disappear from totals | Reject the complete request before any tax determination |
| Duplicate line numbers accepted | Multiple amounts could map to the same snapshot source-line key | Require unique line numbers; preserve non-contiguous valid numbers |
| Header-inclusive tax added to gross | Synthetic gross 100 at inclusive 10% became 109.090910 | Total remains 100; tax breakdown is retained |
| Header withholding added then subtracted | Synthetic base 100 and withholding 10 left payment at 100 | Payment is 90; withholding snapshot remains 10 |

The corrected total uses the net header payment effect already calculated by the Tax engine. It does not reconstruct that effect from tax summary totals, which include amounts already in gross and amounts withheld. Mixed line/header taxes and before/after-tax adjustments are tested together. No financial history is rewritten.

The existing Tax calculation API uses this service; this is live owning-module behavior, not an unused Rental helper. It does not deliver Rental calculation, invoice creation or statutory threshold aggregation.

## Release verification and remaining evidence limits

Tests must prove both accepted outcomes and prevented invalid outcomes. Required commercial cases include threshold equality/crossing/split payment; finite/open-ended periods; missing versus zero KM; pooled versus unpooled mileage; replacement on a period boundary; overlapping downtime; rejected/partial deposit disposition; independent billing order; failed downstream creation and retry; source reversal after invoicing; closed accounting period; and effective tax-rate changes.

Clean SQLite migrations and architecture checks are covered by the backend suite. This patch changes no schema. Production upgrades still require a known installed migration journal, engine/version, backup/restore rehearsal and supported upgrade script. Editing a fresh table-creation migration does not upgrade an already installed table. Never run `migrate:fresh` against an existing user/production database.

Real MySQL/MariaDB concurrency and browser/UAT are separate acceptance evidence. The earlier local MariaDB attempt was blocked by socket restrictions; this research does not cure that environment constraint. Production UAT needs representative agreements, dates, counterparties, tax profiles and expected monetary outputs. Passing unit tests cannot be reported as operator sign-off.

The protected backup remains encrypted after previously documented exact-candidate checks. No candidate succeeded; no guessed variants or brute force were used. Source review continued without it. Thirty-second video sampling, selected full-resolution screens and an unsuccessful transcription experiment do not constitute full continuous review.

Financial workflows, full video coverage and release gates remain visible in the TODO until actually delivered. External research resolves the design direction and narrows policy choices; it cannot honestly turn unimplemented workflows or unexecuted acceptance tests into completed work.
