# Vehicle Rental authoritative domain reconciliation — 2026-08-29

## Purpose

Rebuild `docs/knowledgebase.md` as a self-contained authoritative Vehicle Rental business reference after a fresh end-to-end reconciliation of the TACGL corpus, all four authoritative Vehicle Rental videos, and the latest `worktree-0.0.8` engineering state.

This change is documentation-only. It does not reactivate the retired Vehicle Rental runtime.

## Source authority

The knowledgebase now makes the source hierarchy explicit:

1. TACGL is the primary Vehicle Rental business/accounting source and tie-breaker for repeated transaction/accounting evidence.
2. The four supplied videos are authoritative workflow/operator evidence.
3. `worktree-0.0.8` is authoritative for current AutoERP architecture/module ownership.
4. Legacy implementation defects are not copied merely because they exist in TACGL.
5. When evidence cannot uniquely establish a monetary or eligibility rule, the rule remains unresolved rather than being guessed.

## TACGL verification

Latest re-supply audited: `TACGL(20260829-042529).zip`.

- SHA-256: `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`;
- compressed size: 59,554,116 bytes;
- 456 archive entries;
- 452 non-directory files;
- 420,055,750 bytes uncompressed;
- 114 DBF, 109 FRT, 109 FRX, 46 IDX, 32 CDX, 16 PDF and 5 XLS files.

The hash exactly matches the previously audited canonical TACGL corpus, so this is a re-supply of the same evidence rather than an independent dataset.

## Video verification

The full authoritative video set was rechecked by source hash and end-to-end timeline/key-screen review:

- `1.mp4` — 40:50 — `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf`;
- `Recording 2026-06-21 132314.mp4` — 41:58 — `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa`;
- `2.mp4` — 21:14 — `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f`;
- `ScreenVideo_03-04-2026_18-02-52.mp4` — 12:24 — `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9`.

The Running Chart screen was specifically revalidated as one physical record containing both Lessee and Lessor agreement references plus Vehicle and Driver context.

## Newly strengthened / reconciled conclusions

- Monthly billing is definitively agreement-cycle/anniversary based, not calendar-month-only.
- `FIXED_30_DAY` is the directly evidenced TACGL interpretation for observed partial monthly historical cases (`225,000 x 13/30 = 97,500`; `180,000 x 21/30 = 126,000`). It is not promoted to a universal future default.
- Included/excess KM is interpreted at the applicable agreement billing-cycle boundary; carry-forward and replacement pooling remain unresolved.
- Garage Mileage is definitively a separate operational fact; its commercial inclusion/exclusion remains policy-specific and must not be hardcoded as automatic subtraction.
- Running Chart mileage/time continuation is a controlled operator/business choice, evidenced by explicit continue/clear options. AutoERP should validate continuity without silently forcing it.
- Driver salary/OT/night-out is a shared physical-fact + independent commercial-rate model: Running Chart records facts; Lessee/Lessor agreements independently determine monetary recovery/reimbursement.
- Fuel/Repair owner deduction process is explicit: Lessor Debit Note + evidence reference + allocation. Universal liability/markup is not evidenced.
- Replacement is structurally an explicit original/replacement lineage with exact effective period; exact pricing/downtime rules remain unresolved.
- One-off/third-party rental sourcing is a valid Rental business capability even though TACGL historically routes it through Outside Work.
- Sample Rental invoices being zero-VAT does not prove universal tax exemption because Rental forms explicitly carry VAT/SVAT/SSCL context.

## Reconfirmed semantic corrections

- `OWN...` with `TXNTYPE = 2` = Outside Work, never Owner/Lessor.
- `LCH...` is a broad Labour/service-charge family, not a Rental identifier.
- `VEHTYP` is not authoritative ownership truth because all 1,076 active `scfveh` rows carry `03` despite mixed commercial use.
- Registration punctuation/spacing variants do not define separate physical vehicles.
- Free-text calculation/date narratives are not calculation authority because the corpus contains narrative/stored-value mismatches and impossible narrative dates.

## Reconfirmed financial evidence

- Customer lineage `LCH2005407 -> INV2005519 -> Debtor -> REC2003089 allocation -> GL` remains exact.
- `REC2003089` demonstrates one receipt allocated across multiple invoices.
- `7048-000 RENTAL PAYMENT` has 25 positive debit rows across 21 PRB vouchers totaling 3,396,309 in the inspected corpus.
- Customer commercial values and owner/source payment values differ for the same physical vehicles, reaffirming independent commercial calculations.
- Legacy Rental revenue routed to Workshop sales accounts is rejected as target architecture.

## AutoERP reconciliation

Engineering source checked at `worktree-0.0.8` HEAD `f9e8cd33a9e296ab4b831003339759e0cba95df8` before the documentation write.

- Active Vehicle Rental runtime/frontend remain absent/retired.
- `InvoiceType::Rental` remains a retired-source history type.
- Vehicle exposes `VehicleAvailabilityBlockerInterface` / `vehicle.availability_blocker` as the correct shared availability boundary.
- Finance already has reusable Rental posting/account-role vocabulary (`customer_rental_invoice`, `supplier_rental_invoice`, `rental_deposit`, `rental_revenue`, `rental_expense`).
- The knowledgebase defines module ownership so a future clean Rental rebuild integrates with current modules rather than resurrecting legacy branches wholesale.

## Knowledgebase structure improvement

`docs/knowledgebase.md` now includes an authoritative ambiguity/decision register with evidence status for every major incomplete/conflicting area. Items are categorized as resolved, evidence-derived/partially resolved, or unresolved. Unresolved monetary/eligibility rules explicitly require approved policy/configuration or fail-closed behavior.

## Files changed

- `docs/knowledgebase.md`
- `docs/changes/2026-08-29-vehicle-rental-authoritative-domain-reconciliation.md`

## Verification boundary

- No production PHP/TypeScript file changed.
- No migration, route, provider, tenant feature, Finance seed or test changed.
- No Vehicle Rental runtime was restored.
- No paid tool or GitHub Actions workflow was used.
