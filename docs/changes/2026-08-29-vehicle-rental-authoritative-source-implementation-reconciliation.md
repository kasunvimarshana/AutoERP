# Vehicle Rental authoritative source + implementation reconciliation — 2026-08-29

## Purpose

Re-validate the complete Vehicle Rental source set against the latest `worktree-0.0.8` engineering state, verify whether the current canonical `docs/knowledgebase.md` requires a business-content change, and avoid partial production reactivation of a retired domain.

The audit concluded that the current knowledgebase remains business-correct and self-contained. The newly supplied TACGL ZIP is a repackaging of the exact same 452-file TACGL corpus already reflected in the knowledgebase, and the four mounted videos are the same previously audited source files. Rewriting the knowledgebase again would therefore create documentation churn without changing business meaning.

## Authoritative sources

### TACGL

Latest uploaded package audited:

- `TACGL(20260829-045602).zip`
- outer ZIP SHA-256: `5a92042247317fe7a47ad626020486cf11031ecf9d72477a5781386dff0893b1`
- compressed size: 58,072,193 bytes
- 456 ZIP entries
- 452 non-directory files / 4 directories
- uncompressed size: 420,055,750 bytes
- 114 DBF / 109 FRT / 109 FRX / 46 IDX / 32 CDX / 16 PDF / 5 XLS

This package has a different outer ZIP hash/size from earlier canonical copies. The difference is packaging only.

All 452 inner files were compared with the previously audited canonical archive by normalized relative path, uncompressed size and SHA-256. Every inner file is byte-identical.

Packaging-independent TACGL content-manifest SHA-256:

`5029c7cf44018c16489ffa8d85ea4361353bf2118d940e6349bfbb0297dc3d3e`

Earlier outer ZIP SHA-256 `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313` produces the same content-manifest hash. The packages are therefore one evidence corpus, not independent corroboration.

Future TACGL re-supplies should be compared by normalized inner-content manifest when ZIP wrapper directories/compression differ; outer ZIP hash alone is not a stable corpus identity.

### Videos

The four authoritative videos were re-verified as the same previously audited source set:

- `1.mp4` — SHA-256 `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf`, duration ~40:50
- `Recording 2026-06-21 132314.mp4` — SHA-256 `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa`, duration ~41:58
- `2.mp4` — SHA-256 `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f`, duration ~21:14
- `ScreenVideo_03-04-2026_18-02-52.mp4` — SHA-256 `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9`, duration ~12:24

The business interpretation remains unchanged: one physical Running Chart/fact stream, independent Lessee/customer and Lessor/owner commercial calculations, separate agreements/rates, shared physical vehicle identity/availability, and explicit financial allocation/reconciliation.

## TACGL re-checks

The latest package reproduces the same key evidence:

- `scfveh`: 1,076 active rows and six normalized duplicate-registration groups
- all active `scfveh.VEHTYP = 03`, so `VEHTYP` is not authoritative ownership truth
- `scfchr` Rental component codes have zero master rates
- `OWN...` with `TXNTYPE = 2` is Outside Work
- `LCH...` with `TXNTYPE = 3` is broad Labour/service charge, not Rental-specific
- exact customer chain `LCH2005407 -> INV2005519 -> Debtor -> REC2003089 allocation -> GL`
- non-calendar monthly cycles such as `25 -> 24` and `18 -> 17`
- fixed-30-day partial-period precedents without proof of a universal future `/30` rule
- structured excess-KM arithmetic and free-text/data-quality conflicts
- corrected `544 x 300` Outside Work amount from deleted 163,000 to active 163,200
- direct `7048-000 RENTAL PAYMENT` owner/source payment activity
- deleted/re-entry history requiring explicit immutable correction/reversal lineage

No new conflicting business rule was introduced by the repackaged archive.

## Current AutoERP implementation audit

Engineering source audited before this change:

`worktree-0.0.8` at `0a47141fde3111fc0b2b56e98c5c9503acf1af19`.

Reconciliation findings:

- no active `app/Modules/VehicleRental`
- no active Vehicle Rental frontend
- `bootstrap/providers.php` has no Vehicle Rental provider
- `InvoiceType::Rental` is explicitly a retired-source type
- Finance retains clean Rental posting/profile vocabulary but no active Rental source implementation
- Vehicle exposes `VehicleAvailabilityBlockerInterface` / `VehicleAvailabilityService`
- Vehicle Service registers its own availability blocker through the Vehicle contract
- Vehicle already has effective-dated `VehicleOwnership` history for Customer/Supplier/Company and ownership classifications such as Leased/Rented/ThirdParty
- `VehicleOwnership` is physical/legal/party history; it is not the Rental Lessor Agreement or Rental commercial supply-coverage aggregate

## Relationship decision clarified

Do not duplicate owner/vehicle identity in Rental and do not expand Vehicle ownership into Rental commercial logic.

Correct separation:

```text
VehicleOwnership
    -> Vehicle-owned physical/legal/party relationship history

Rental Lessor Agreement + Rental supply coverage
    -> Rental-owned commercial source, effective period, rates and settlement eligibility
```

Rental may reference the applicable Vehicle/owner identity, but the Lessor Agreement remains the commercial authority.

## Production-change decision

No production PHP/TypeScript/migration/route/provider/Finance-seed change is justified by this evidence refresh.

Partially reactivating one old Rental component would create an invalid state where the UI/provider/invoice/accounting surface is live without the full agreement, supply/use, Running Chart, independent calculation, exactly-once consumption, reversal, concurrency and unresolved-policy controls required by the business.

The safe maintainable decision is to keep Rental retired until a complete minimum production slice is implemented against current module contracts and explicit policies for every monetary/eligibility rule it uses.

This is a root-design decision, not a backward-compatibility workaround.

## Knowledgebase decision

`docs/knowledgebase.md` was deeply re-read against this TACGL/video/code audit. Its domain invariants, ambiguity register, validation/state/concurrency guidance, module ownership, source traceability and current implementation reconciliation remain correct. Because the latest TACGL package contains the exact same inner corpus and no business rule changed, the file is intentionally left unchanged rather than rewritten only to change a ZIP wrapper filename/hash.

The stable content-manifest fingerprint and latest package-level verification are recorded here in append-only project memory so future agents can recognize repackaged copies without manufacturing additional business confidence.

## Files changed

- `docs/changes/2026-08-29-vehicle-rental-authoritative-source-implementation-reconciliation.md`

## Verification

- source branch HEAD checked before writing
- latest TACGL outer package hashed
- all 452 inner files content-compared
- video hashes/durations re-verified
- relevant Vehicle, Vehicle Service, Invoice, Finance and provider boundaries inspected
- current `docs/knowledgebase.md` re-read against the evidence and code
- no paid tools used
- no GitHub Actions used
- no production runtime file changed
