# AutoERP Enterprise Knowledge Base Master Canonical Rewrite

## Context & Motivation
The user requested a thorough, end-to-end audit, review, and in-depth inspection of the entire AutoERP project, followed by a complete cleanup, restructuring, and rewriting of `docs/knowledgebase.md`.

Previously, `docs/knowledgebase.md` focused predominantly on the Vehicle Rental domain with partial and fragmented references to other platform subsystems. To serve as the true, authoritative, self-contained single source of truth for the entire AutoERP platform (all 31 business modules) and align strictly with historical Visual FoxPro enterprise printouts and production business evidence, `docs/knowledgebase.md` was rewritten from the ground up.

---

## Changes Implemented

### 1. Unified 10-Section Master Architectural Structure
Rewrote [`docs/knowledgebase.md`](file:///d:/project-new/AutoERP/docs/knowledgebase.md) into 10 cohesive sections:
1. **Executive System Overview & Canonical Authority:**
   - 8 core architectural axioms (understanding before changing, no inventing money, speed/clarity, root cause fixes, no legacy patches, concurrency by default, immutable transactions, KISS + explicit in migrations).
   - 5-tier system source precedence hierarchy (production evidence &rarr; root rules &rarr; narrowest integrity rule &rarr; explicit documented policies &rarr; absence of automatic financial effect).
2. **Platform Architecture & Cross-Cutting Foundations:**
   - Dual operating planes (Platform Control Plane vs. Tenant Business Plane).
   - Multi-tenant data scoping (`TenantOwnedModel`, `scopeForContext`).
   - Concurrency architecture (Optimistic Concurrency Control with integer `row_version` and `expected_version`, pessimistic row locking via `DB::transaction()` with `lockForUpdate()`).
   - Exact decimal arithmetic standard via `DecimalMath` (BCMath string math).
   - Append-only audit trails and event streams.
3. **Module Ownership & Domain Boundary Matrix:**
   - Exhaustive coverage of all 31 modules under `app/Modules/*`: `Audit`, `Auth`, `Configuration`, `Core`, `Customer`, `Expense`, `Finance`, `Hr`, `Idempotency`, `Inventory`, `Invoice`, `Item`, `OrganizationUnit`, `Payment`, `PrivateObject`, `Promotion`, `Purchase`, `ReferenceData`, `Reporting`, `Selling`, `Sequence`, `Supplier`, `Tax`, `Tenant`, `UOM`, `User`, `Vehicle`, `VehicleRental`, `VehicleService`, `Voucher`, and `Warehouse`.
   - Specific public boundaries, ownership responsibilities, and prohibition of inter-module workarounds.
4. **Master Data Architecture:**
   - Fleet asset governance in `Vehicle` (`GET /api/v1/vehicles/lookup/active`, availability blockers).
   - Debtor customer profiles in `Customer`, creditor supplier profiles in `Supplier`.
   - Authoritative employee driver and workshop mechanic directory in `Hr`.
   - Product, labor, and service catalog in `Item` and units in `UOM`.
5. **Supply Chain, Inventory, Workshop & Auxiliary Operations:**
   - Procurement lifecycles and Fast Purchase orchestration in `Purchase`.
   - Multi-dimension inventory balances ($\text{Available} = \text{On-Hand} - \text{Reserved} - \text{Allocated}$) and FIFO/WAC real-time valuation layers in `Inventory`.
   - Workshop maintenance in `VehicleService` with mechanical custody blocking on commercial rental dispatches.
   - Corporate operational expenses in `Expense`.
   - Customer loyalty programs and service promotions in `Promotion`.
6. **Commercial Vehicle Rental Enterprise Domain:**
   - Dual-sided contract model: `CustomerAgreement` (receivables) vs. `OwnerAgreement` (liabilities) with zero coupling.
   - Agreement lifecycle and successor lineage (`supersedes_agreement_id`) preserving security deposit origins.
   - Custody tracking, handover/return odometers, and atomic vehicle replacement (`VehicleUseService::replace()`) with shared monthly allowance and amber visual badges (`For Repl. <reg_number>`).
   - Daily Running Chart operational evidence register: civil business timezone timestamps, duty ON/OFF, odometer comparative/cumulative mileage, overtime tiers (Normal, Double/Sunday, Triple/Holiday), night outs, toll pass outlays, and finalized physical evidence rules.
   - Debtor revenue billing track: base rent invoicing (`actual_calendar_days_v1`), commercial excess mileage assessment, usage invoices, security deposit capacity tracking.
   - Creditor owner settlement track: closed-loop reconciliation formulas, Statement of Accounts Detail I (A4 Portrait), Statement of Accounts Detail II (18-column A4 Landscape), and statutory 10% Withholding Tax ledger (`:3305-000 WHT`).
   - Fleet expense 4-way allocation matrix (`RentalExpense`).
   - Vehicle finance and bank leasing contracts (`VehicleFinanceAgreement`).
   - Incident evidence with SHA-256 tamper-evident private proof attachments.
7. **Financial Core, Invoicing, Tax & Treasury:**
   - Single canonical source of truth for commercial billing in `Invoice`.
   - Treasury and cash movement in `Payment` with allocation tracking and cheque generation.
   - Double-entry general ledger in `Finance` with posting profiles (semantic account roles) and fiscal period guards.
   - Statutory taxation engine in `Tax` with immutable tax rate snapshots.
   - Idempotency engine (`Idempotency`) with SHA-256 payload fingerprinting and request lock coordination.
   - System reference catalogs and civil business timezone (`Asia/Colombo`) in `ReferenceData`.
8. **Comprehensive Enterprise Reporting Suite:**
   - Detailed specification of the 8 Vehicle Rental reports and core enterprise reports across Finance, Inventory, and Service, including page orientations (A4 Portrait vs. A4 Landscape) and legacy FoxPro FRX mappings.
9. **Prohibited Anti-Patterns & Architecture Violations:**
   - Explicit prohibition of direct vehicle-agreement foreign keys, in-place transaction mutations, cross-module workarounds, fictitious owner costs for company vehicles, hardcoded magic account numbers, raw database IDs in UI, and browser client timezone drift.
10. **Developer & AI-Agent Governance Protocol:**
    - Step-by-step decision sequence and exhaustive 10-point Definition of Done quality checklist.

---

## Verification & Quality Assurance
- Formatted with standard GitHub Flavored Markdown and validated for readability and syntax integrity.
- Verified consistency with existing codebase migrations, controllers, services, Blade report templates, and TypeScript UI implementations across all 31 modules.
- Complies strictly with repository rules defined in `AGENTS.md` and `RULES.md`.
