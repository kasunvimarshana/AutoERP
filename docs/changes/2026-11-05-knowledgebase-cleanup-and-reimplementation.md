# Vehicle Rental Knowledgebase Cleanup and Reimplementation

## Context
`docs/knowledgebase.md` had accumulated over 1,200 lines of repetitive dated audit logs, repeated SHA-256 checks from multiple past review cycles, environment/runner status notes, and dated session journals appended at the end of the file. Meanwhile, newly built production capabilities—including Rental Reservations, Vehicle Finance & Bank Leasing, Rental Fleet Expenses, Private File Proof Attachments, Successor Agreement Architecture, the 8-report Comprehensive Business Intelligence suite, and modern UI/UX ergonomics—were either missing or only described piecemeal in dated appendices.

## Key Changes
- **Reimplemented Canonical Knowledgebase (`docs/knowledgebase.md`)**:
  - Re-anchored the document as the permanent, authoritative domain reference and architectural specification for Vehicle Rental in AutoERP.
  - Purged obsolete transient execution notes ("shell cannot resolve github.com", "PR #127 candidate", dated SHA re-verification logs) and separated historical session notes into `docs/changes/`.
  - Consolidated the source evidence register (§2) into a clear, unified register of TACGL database artifacts and video workflows.
  - Fully documented all newly implemented sub-domains:
    - **Rental Reservations & Booking Inquiries (§6)**: Inquiry model, status transitions, version checking, and conversion to Customer Agreements.
    - **Successor Agreement Architecture (§5.4)**: Non-disruptive draft creation while predecessor is active, deposit non-duplication, and atomic boundary cutover.
    - **Vehicle Finance & Bank Leasing (§10)**: Financing agreements, amortization terms, installment lifecycles, automated due status sync, and AP payable invoice creation.
    - **Rental Fleet Expenses & Direct Cost Recovery (§11)**: Category tracking (`RentalExpenseType`) and the 4-destination allocation matrix (`RentalExpenseAllocationStatus`).
    - **Incident Evidence & Private File Proofs (§12)**: Factual non-monetary register, review actions, and tamper-evident private tenant storage.
    - **Comprehensive Reporting & Business Intelligence (§14)**: Full operational matrix for all 8 official reports, including the 31-day Running Chart matrix sheet (`pmdeblogsheet2.frx` / `pmcre1logsheet2.frx`).
    - **Concurrency, Multi-Tenancy & UX Contract (§15)**: `expected_version` / `row_version` locking, tenant scoping, append-only history tables, controlled lookups, zero raw IDs, and tenant business calendar defaults (`businessDateInputValue()`).
    - **Legacy Anti-Patterns & Prohibited Traps (§16)**: Prohibited design mistakes to preserve architectural health.

## Verification
- Verified schema and domain alignment against `app/Modules/VehicleRental/` and `resources/js/modules/vehicle-rental/`.
- Ran `git diff --check` to ensure clean whitespace and formatting.
