# AutoERP Vehicle Rental Module: Complete Technical & Operational Guide

**Status:** Canonical Master Technical Specification, Architecture Manual & Onboarding Guide  
**Module Directory (Backend):** [`app/Modules/VehicleRental`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental)  
**Frontend Directory:** [`resources/js/modules/vehicle-rental`](file:///d:/project-new/AutoERP/resources/js/modules/vehicle-rental)  
**Timezone Standard:** `Asia/Colombo` (Operational Civil Business Calendar)  
**Precision Arithmetic:** Arbitrary-Precision String Math via [`DecimalMath`](file:///d:/project-new/AutoERP/app/Modules/Core/Services/DecimalMath.php) (BCMath, scale 6)  
**Test Suite Coverage:** 306 Backend PHPUnit Tests (2,477 Assertions) · 33 Frontend Vitest Suites (154 Tests) · TypeScript Strict (0 Errors)  

---

## Table of Contents

1. [Executive Overview & Business Mission](#1-executive-overview--business-mission)
   - 1.1 [Core Business Architecture: Dual-Track Operational Decoupling](#11-core-business-architecture-dual-track-operational-decoupling)
   - 1.2 [Fundamental Architectural Invariants](#12-fundamental-architectural-invariants)
   - 1.3 [High-Level Architecture Diagram](#13-high-level-architecture-diagram)
   - 1.4 [Directory & Codebase Layout](#14-directory--codebase-layout)
2. [Complete Database Schema Reference](#2-complete-database-schema-reference)
   - 2.1 [Master Entity-Relationship Diagram (ERD)](#21-master-entity-relationship-diagram-erd)
   - 2.2 [The Core Database Tables & Column Definitions](#22-the-core-database-tables--column-definitions)
   - 2.3 [Immutable History & Event Tables](#23-immutable-history--event-tables)
   - 2.4 [The Agreement Terms JSON Schema](#24-the-agreement-terms-json-schema)
   - 2.5 [Complete Domain Enums Catalog (38 Enums)](#25-complete-domain-enums-catalog-38-enums)
3. [Subsystems, State Machines & Operational Lifecycles](#3-subsystems-state-machines--operational-lifecycles)
   - 3.1 [Rental Reservations & Booking Inquiries](#31-rental-reservations--booking-inquiries)
   - 3.2 [Customer Commercial Agreements & Successor Lineage](#32-customer-commercial-agreements--successor-lineage)
   - 3.3 [Vehicle Owner Lease Agreements & Sourcing](#33-vehicle-owner-lease-agreements--sourcing)
   - 3.4 [Vehicle Physical Custody (`VehicleUse`)](#34-vehicle-physical-custody-vehicleuse)
   - 3.5 [Mid-Lease Atomic Vehicle Swaps (Replacements)](#35-mid-lease-atomic-vehicle-swaps-replacements)
   - 3.6 [Driver Assignments & Reassignments](#36-driver-assignments--reassignments)
   - 3.7 [Daily Running Charts & Telematics Tracking](#37-daily-running-charts--telematics-tracking)
   - 3.8 [Fleet Operating Expenses & 4-Way Allocation Matrix](#38-fleet-operating-expenses--4-way-allocation-matrix)
   - 3.9 [Damage Incidents & Private Proofs](#39-damage-incidents--private-proofs)
   - 3.10 [Vehicle Finance & Bank Leasing Contracts](#310-vehicle-finance--bank-leasing-contracts)
   - 3.11 [Agreement Financial Adjustments](#311-agreement-financial-adjustments)
4. [The Two-Stage Invoicing Engine](#4-the-two-stage-invoicing-engine)
   - 4.1 [Why Two-Stage Invoicing?](#41-why-two-stage-invoicing)
   - 4.2 [Stage 1: Advance / Provisional Base Rent Invoicing](#42-stage-1-advance--provisional-base-rent-invoicing)
   - 4.3 [Running Chart Attachment & Operational Rate Calculations](#43-running-chart-attachment--operational-rate-calculations)
   - 4.4 [Stage 2: Supplementary Usage Invoicing (Mode A)](#44-stage-2-supplementary-usage-invoicing-mode-a)
   - 4.5 [Negative Delta Handling & Credit Notes](#45-negative-delta-handling--credit-notes)
   - 4.6 [Consolidated Revision Invoicing (Mode B)](#46-consolidated-revision-invoicing-mode-b)
   - 4.7 [Cancellation & Source Restoration Engine](#47-cancellation--source-restoration-engine)
   - 4.8 [Complete End-to-End Invoicing Numeric Case Study](#48-complete-end-to-end-invoicing-numeric-case-study)
5. [Owner Settlement AP Billing & Disbursements](#5-owner-settlement-ap-billing--disbursements)
   - 5.1 [Commercial Workflow of Fleet Sourcing](#51-commercial-workflow-of-fleet-sourcing)
   - 5.2 [Gross Lease Earnings & Operational Usage Reimbursement](#52-gross-lease-earnings--operational-usage-reimbursement)
   - 5.3 [Statutory 10% Withholding Tax (WHT :3305-000)](#53-statutory-10-withholding-tax-wht-3305-000)
   - 5.4 [Fleet Maintenance Chargeback Deductions](#54-fleet-maintenance-chargeback-deductions)
   - 5.5 [Deduction Capping Safeguard](#55-deduction-capping-safeguard)
   - 5.6 [Settlement Cancellation & Complete Restoration](#56-settlement-cancellation--complete-restoration)
   - 5.7 [Owner Settlement Numeric Case Study](#57-owner-settlement-numeric-case-study)
6. [Multi-Agreement Financial Settlements & Payments](#6-multi-agreement-financial-settlements--payments)
   - 6.1 [Single Agreement Payments vs Party-Wide Settlements](#61-single-agreement-payments-vs-party-wide-settlements)
   - 6.2 [Interactive Document Allocation Grid](#62-interactive-document-allocation-grid)
   - 6.3 [Unapplied Advances & Surplus Pooling](#63-unapplied-advances--surplus-pooling)
   - 6.4 [Payment Reversals & Refunds](#64-payment-reversals--refunds)
7. [Financial Ledger Integrations & Accounting Impact](#7-financial-ledger-integrations--accounting-impact)
   - 7.1 [Integration with `Modules\Invoice`](#71-integration-with-modulesinvoice)
   - 7.2 [Integration with `Modules\Payment`](#72-integration-with-modulespayment)
   - 7.3 [Complete General Ledger Double-Entry Posting Matrix](#73-complete-general-ledger-double-entry-posting-matrix)
8. [Enterprise Reporting Suite](#8-enterprise-reporting-suite)
   - 8.1 [Telematics Statement of Account (HTML, Print, PDF)](#81-telematics-statement-of-account-html-print-pdf)
   - 8.2 [The 8 Core Analytical Reports](#82-the-8-core-analytical-reports)
9. [Frontend Workspaces & UI/UX Standards](#9-frontend-workspaces--uiux-standards)
   - 9.1 [Frontend Architecture & Component Hierarchy](#91-frontend-architecture--component-hierarchy)
   - 9.2 [Dedicated Invoices and Owner Bills Workspace](#92-dedicated-invoices-and-owner-bills-workspace)
   - 9.3 [Agreements Management Workspace](#93-agreements-management-workspace)
   - 9.4 [Vehicle Custody & Running Chart Registers](#94-vehicle-custody--running-chart-registers)
   - 9.5 [UI/UX Invariants & Design Principles](#95-uiux-invariants--design-principles)
   - 9.6 [End-to-End User Journeys](#96-end-to-end-user-journeys)
10. [Complete REST API Specification (65+ Endpoints)](#10-complete-rest-api-specification-65-endpoints)
11. [Developer Verification, Testing & Extension Guide](#11-developer-verification-testing--extension-guide)

---

## 1. Executive Overview & Business Mission

The **Vehicle Rental** module (`VehicleRental`) is AutoERP's high-throughput operational and commercial management engine for vehicle rental operators, corporate chauffeur transport services, commercial fleet leasing, and private car-hire businesses.

### 1.1 Core Business Architecture: Dual-Track Operational Decoupling

A real-world commercial vehicle rental company does not simply purchase cars and rent them to retail customers. Commercial fleet operations involve two entirely independent operational and accounting realities:

1. **Customer Track (Debtor / Accounts Receivable - AR):**
   - Corporate clients rent vehicles on a monthly, weekly, or daily basis.
   - Revenue streams comprise **Monthly Base Rent**, **Excess Mileage Charges**, **Driver Overtime** (Normal 1.5x, Double 2.0x, Triple 3.0x), **Driver Night-Out Allowances**, **Highway Toll Outlays**, and **Ad-Hoc Additions**.
   - Contractual downtime rebates, promotional discounts, and accident adjustments reduce receivable balances.

2. **Owner Track (Creditor / Accounts Payable - AP):**
   - Rental companies frequently lease vehicles from private vehicle owners or external fleet suppliers to satisfy client demand without massive balance-sheet capital expenditure.
   - Liability costs comprise **Lessor Lease Rent**, **Agreed Usage Mileage**, **Driver Salary Reimbursements**, minus **Statutory 10% Withholding Tax (WHT)** and **Fleet Maintenance Chargebacks**.

3. **Fleet Custody Track (The Physical Bridge):**
   - The customer agreement terms **never** dictate owner agreement costs; the owner's payable rates **never** dictate customer billing.
   - The two commercial sides are decoupled and unified strictly at the point of **Physical Vehicle Custody** (`vehicle_rental_uses`) and **Daily Running Charts** (`vehicle_rental_running_charts`).

```
+---------------------------------------------------------------------------------+
|                       AutoERP Dual-Track Architecture                           |
|                                                                                 |
|   [ Customer (Lessee) ]                     [ Vehicle Owner (Lessor) ]          |
|            |                                            |                       |
|   Customer Agreement (Revenue)               Owner Agreement (Lease Cost)       |
|            \                                            /                       |
|             \                                          /                        |
|              +--> [ Physical Vehicle Custody ] <------+                         |
|                   [     (VehicleUse)         ]                                  |
|                             |                                                   |
|                             v                                                   |
|                   [ Daily Running Charts ]                                      |
|                   (Trip Sheets & Telematics)                                    |
|                             |                                                   |
|              +--------------+--------------+                                    |
|              |                             |                                    |
|              v                             v                                    |
|   [ Customer Billing ]            [ Owner Settlements ]                         |
|   Stage 1 Base Rent Invoice       Gross Lease Rent                              |
|   Stage 2 Usage Invoice           + Usage Charges                               |
|   (Excess KM, OT, Tolls)          - 10% Statutory WHT                           |
|                                   - Fleet Maintenance Chargebacks               |
|              |                             |                                    |
|              v                             v                                    |
|   AR Ledger (Sales)               AP Ledger (Supplier Bills)                    |
+---------------------------------------------------------------------------------+
```

### 1.2 Fundamental Architectural Invariants

Every service, controller, and database operation in `VehicleRental` enforces the following non-negotiable architectural rules:

1. **"Do Not Invent Money" Principle:**
   - Physical evidence (trip odometers, shift hours, fuel receipts, accident reports) is recorded as immutable factual history.
   - **No financial invoice, credit note, debit note, owner payable voucher, or GL journal entry is ever created automatically by the system without explicit user review, authorized approval, and verified contractual entitlement.**
2. **No Fictitious Owner Costs for Company-Owned Fleet:**
   - If a vehicle is owned directly by the tenant company (recorded in `vehicles` table with company ownership), **zero artificial owner agreements or supplier bills are manufactured**.
3. **Decoupled Commercial Independence:**
   - Modifying a customer's rate structure does not modify the owner's lease rate. Each side has its own currency, billing basis, terms, and tax schedules.
4. **Optimistic Concurrency Control (OCC):**
   - Every read-modify-write operation asserts `expected_version === row_version`. Stale concurrent writes fail immediately with HTTP 409 Conflict.
5. **Exact Decimal Precision Arithmetic:**
   - Floating-point arithmetic (`float`, `double`) is strictly banned in financial, distance, and quantity calculations. All numbers use arbitrary-precision string math via [`DecimalMath`](file:///d:/project-new/AutoERP/app/Modules/Core/Services/DecimalMath.php) (BCMath with scale 6).
6. **Civil Timezone Standard (`Asia/Colombo`):**
   - Rental cutovers, calendar months, duty shifts, and vehicle custody boundaries evaluate strictly against the tenant's civil business timezone (`Asia/Colombo`). Client UTC offsets cannot misalign operational boundaries.
7. **Strict Immutability & Append-Only History:**
   - Deleting active or historical business records is strictly prohibited (`LogicException`). Modifying an activated agreement, vehicle use, or running chart creates an append-only audit record in dedicated history tables (`*_history`).

### 1.3 High-Level Architecture Diagram

```mermaid
flowchart TD
    subgraph CustomerCommercial["Customer Commercial Track"]
        RES["Rental Reservation<br/>(Inquiry & Booking)"]
        CA["Customer Agreement<br/>(Terms JSON & Deposit)"]
        RES -->|convert| CA
        CYCLE["Billing Cycle<br/>(Monthly / Custom Period)"]
        CA --> CYCLE
        INV1["Stage 1 Provisional<br/>Base Rent Invoice"]
        INV2["Stage 2 Supplementary<br/>Usage Invoice"]
        INV_REV["Mode B Consolidated<br/>Revision Invoice"]
        CN["Credit Note Invoice<br/>(Downtime Rebates)"]
        CYCLE -->|Stage 1| INV1
        CYCLE -->|Stage 2 Mode A| INV2
        CYCLE -->|Stage 2 Mode B| INV_REV
        CYCLE -->|Negative Delta| CN
    end

    subgraph PhysicalFleet["Physical Fleet Custody Track"]
        VU["Vehicle Use<br/>(Assignment & Custody)"]
        CA -->|assign vehicle| VU
        VU_SWAP["Atomic Replacement<br/>(Mid-Lease Vehicle Swap)"]
        VU -.->|breakdown / service| VU_SWAP
        VU_SWAP -.->|continue custody| VU
        RC["Running Chart<br/>(Daily Trip Log & Odometers)"]
        VU -->|daily logs| RC
        REASSIGN["Reassignments Register<br/>(Driver / Vehicle)"]
        RC -.->|driver swap| REASSIGN
    end

    subgraph OwnerCommercial["Owner Lease Track"]
        OA["Owner Agreement<br/>(Vehicle Lease Terms)"]
        OA -.->|source vehicle| VU
        SETTLE["Owner Settlement Bill<br/>(Itemized AP Bill)"]
        OA --> SETTLE
        WHT["Statutory 10% WHT<br/>(:3305-000 Deduction)"]
        MAINT["Fleet Maintenance<br/>Expense Chargebacks"]
        SETTLE --> WHT
        SETTLE --> MAINT
    end

    subgraph CrossCutting["Cross-Cutting Operations"]
        EXP["Rental Expenses<br/>(Fuel, Tolls, Repairs)"]
        INC["Damage Incidents<br/>(Evidence & Proofs)"]
        FIN["Vehicle Finance<br/>(Bank Leasing Contracts)"]
        PAY["Multi-Agreement Payments<br/>(Receipts & Disbursements)"]
    end

    RC -->|aggregate usage| CYCLE
    EXP -->|tolls / recoveries| CYCLE
    EXP -->|owner deductions| SETTLE
    INV1 --> PAY
    INV2 --> PAY
    SETTLE --> PAY
```

### 1.4 Directory & Codebase Layout

```
AutoERP
├── app/Modules/VehicleRental/
│   ├── Constants/                     # AgreementFields, OperationalFields, IncidentFields
│   ├── Data/                          # AgreementContext DTO
│   ├── Database/Migrations/           # 28 Core Table Migrations
│   ├── Database/UpgradeMigrations/    # Schema hardening & indexing migrations
│   ├── Enums/                         # 38 Strict PHP 8.2 Backed Enums
│   ├── Http/
│   │   ├── Controllers/               # 21 Specialized REST API Controllers
│   │   ├── Requests/                  # FormRequest validation & OCC assertions
│   │   └── Resources/                 # JSON API Serialization Resources
│   ├── Models/                        # 30 Eloquent Models with audit relations
│   ├── Policies/                      # Fine-grained authorization policies
│   ├── Routes/api.php                 # Route definitions and middleware gates
│   ├── Services/                      # 38 High-cohesion domain services
│   └── Tests/                         # 41 PHPUnit Test Classes (306 Tests)
└── resources/js/modules/vehicle-rental/
    ├── api clients/                   # agreementApi.ts, rentalBillingApi.ts, etc.
    ├── components/                    # Modal wizards, history panels, grids
    ├── pages/                         # Dedicated register and report pages
    └── reports/                       # 7 Interactive report page views
```

---

## 2. Complete Database Schema Reference

The Vehicle Rental module database comprises **28 core relational tables**, strictly isolated by `tenant_id` and `organization_unit_id`.

### 2.1 Master Entity-Relationship Diagram (ERD)

```mermaid
erDiagram
    vehicle_rental_customer_agreements ||--o{ vehicle_rental_customer_agreements_history : audits
    vehicle_rental_customer_agreements ||--o{ vehicle_rental_uses : assigns
    vehicle_rental_customer_agreements ||--o{ vehicle_rental_customer_base_charges : incurs
    vehicle_rental_customer_agreements ||--o{ vehicle_rental_customer_usage_charges : incurs
    vehicle_rental_customer_agreements ||--o{ vehicle_rental_billing_cycles : bills
    vehicle_rental_customer_agreements ||--o{ vehicle_rental_agreement_adjustments : adjusts

    vehicle_rental_owner_agreements ||--o{ vehicle_rental_owner_agreements_history : audits
    vehicle_rental_owner_agreements ||--o{ vehicle_rental_owner_base_charges : incurs
    vehicle_rental_owner_agreements ||--o{ vehicle_rental_owner_usage_charges : incurs
    vehicle_rental_owner_agreements ||--o{ vehicle_rental_billing_cycles : bills
    vehicle_rental_owner_agreements ||--o{ vehicle_rental_agreement_adjustments : adjusts

    vehicle_rental_uses ||--o{ vehicle_rental_use_history : audits
    vehicle_rental_uses ||--o{ vehicle_rental_running_charts : records
    vehicle_rental_uses ||--o{ vehicle_rental_uses : "replaces / replaced_by"

    vehicle_rental_running_charts ||--o{ vehicle_rental_running_chart_history : audits
    vehicle_rental_running_charts ||--o{ vehicle_rental_billing_cycle_charts : attaches

    vehicle_rental_billing_cycles ||--o{ vehicle_rental_billing_cycle_charts : contains
    vehicle_rental_billing_cycles ||--o{ vehicle_rental_billing_cycle_items : itemizes

    vehicle_rental_expenses ||--o{ vehicle_rental_expense_allocations : allocates

    vehicle_rental_incidents ||--o{ vehicle_rental_incident_events : tracks
    vehicle_rental_incidents ||--o{ vehicle_rental_incident_attachments : attaches

    vehicle_rental_finance_agreements ||--o{ vehicle_rental_finance_installments : amortizes
    vehicle_rental_finance_agreements ||--o{ vehicle_rental_finance_status_histories : tracks
```

### 2.2 The Core Database Tables & Column Definitions

Below are the schema specifications for the primary relational tables:

#### 1. `vehicle_rental_customer_agreements`
Stores commercial contracts with customers (lessees).

| Column | Type | Nullable | Key / Default | Description |
| :--- | :--- | :---: | :---: | :--- |
| `id` | `bigint unsigned` | No | PK, Auto Inc | Unique identifier |
| `tenant_id` | `bigint unsigned` | No | Index | Multi-tenant company identifier |
| `organization_unit_id`| `bigint unsigned` | No | Index | Operating branch / business unit |
| `customer_id` | `bigint unsigned` | No | FK | Customer party ID (`parties.id`) |
| `reference` | `varchar(100)` | No | Unique index | Agreement contract reference code |
| `rental_basis` | `varchar(32)` | No | `monthly` | `daily`, `weekly`, `monthly`, `calendar_month` |
| `starts_on` | `date` | No | Index | Lease commencement date |
| `ends_on` | `date` | Yes | Nullable | Contract expiry date (`null` = open-ended) |
| `executing_date` | `date` | Yes | Nullable | Legal signing date |
| `closed_at` | `timestamp` | Yes | Nullable | Exact timestamp of agreement closure |
| `status` | `varchar(32)` | No | `draft` | `draft`, `active`, `closed` |
| `terms` | `json` | No | `{}` | 12-key JSON terms schema (base rate, km, OT, etc.) |
| `currency_id` | `bigint unsigned` | No | FK | Contract billing currency |
| `predecessor_agreement_id` | `bigint unsigned` | Yes | FK | Lineage pointer to previous contract |
| `successor_agreement_id` | `bigint unsigned` | Yes | FK | Lineage pointer to successor contract |
| `row_version` | `int unsigned` | No | `1` | Optimistic Concurrency Control counter |
| `created_at` / `updated_at` | `timestamps` | No | | Record audit timestamps |

#### 2. `vehicle_rental_owner_agreements`
Stores fleet sourcing lease contracts with external vehicle owners / suppliers.

| Column | Type | Nullable | Key / Default | Description |
| :--- | :--- | :---: | :---: | :--- |
| `id` | `bigint unsigned` | No | PK, Auto Inc | Unique identifier |
| `tenant_id` | `bigint unsigned` | No | Index | Multi-tenant company identifier |
| `organization_unit_id`| `bigint unsigned` | No | Index | Operating branch identifier |
| `supplier_id` | `bigint unsigned` | No | FK | Owner supplier party ID (`parties.id`) |
| `vehicle_id` | `bigint unsigned` | No | FK | Specific physical vehicle sourced (`vehicles.id`) |
| `reference` | `varchar(100)` | No | Unique index | Owner lease agreement reference code |
| `rental_basis` | `varchar(32)` | No | `monthly` | `daily`, `weekly`, `monthly`, `calendar_month` |
| `starts_on` | `date` | No | Index | Sourcing start date |
| `ends_on` | `date` | Yes | Nullable | Sourcing end date |
| `status` | `varchar(32)` | No | `draft` | `draft`, `active`, `closed` |
| `terms` | `json` | No | `{}` | JSON terms schema (deposit prohibited) |
| `currency_id` | `bigint unsigned` | No | FK | Contract payable currency |
| `row_version` | `int unsigned` | No | `1` | OCC version counter |

#### 3. `vehicle_rental_uses`
Tracks physical custody, assignment, key handover, and vehicle returns.

| Column | Type | Nullable | Key / Default | Description |
| :--- | :--- | :---: | :---: | :--- |
| `id` | `bigint unsigned` | No | PK, Auto Inc | Unique identifier |
| `tenant_id` | `bigint unsigned` | No | Index | Multi-tenant company identifier |
| `organization_unit_id`| `bigint unsigned` | No | Index | Operating branch identifier |
| `customer_agreement_id` | `bigint unsigned` | No | FK | Associated customer contract |
| `owner_agreement_id` | `bigint unsigned` | Yes | FK | Sourcing agreement (`null` if company owned) |
| `vehicle_id` | `bigint unsigned` | No | FK | Physical vehicle identifier |
| `driver_mode` | `varchar(32)` | No | `with_driver` | `with_driver`, `self_drive` |
| `driver_employee_id` | `bigint unsigned` | Yes | FK | Default chauffeur (`hr_employees.id`) |
| `status` | `varchar(32)` | No | `planned` | `planned`, `in_custody`, `returned`, `cancelled` |
| `starts_at` | `timestamp` | No | Index | Planned assignment start timestamp |
| `ends_at` | `timestamp` | Yes | Nullable | Planned assignment end timestamp |
| `handed_over_at` | `timestamp` | Yes | Nullable | Actual physical key handover timestamp |
| `returned_at` | `timestamp` | Yes | Nullable | Actual physical vehicle check-in timestamp |
| `handover_odometer` | `decimal(12,2)` | Yes | Nullable | Odometer reading at vehicle collection |
| `return_odometer` | `decimal(12,2)` | Yes | Nullable | Odometer reading at vehicle check-in |
| `replaces_use_id` | `bigint unsigned` | Yes | FK | If replacement: points to original custody use |
| `replaced_by_use_id`| `bigint unsigned` | Yes | FK | If replaced: points to replacement custody use |
| `row_version` | `int unsigned` | No | `1` | OCC version counter |

#### 4. `vehicle_rental_running_charts`
Master daily trip log recording telematics, shift hours, and distance.

| Column | Type | Nullable | Key / Default | Description |
| :--- | :--- | :---: | :---: | :--- |
| `id` | `bigint unsigned` | No | PK, Auto Inc | Unique identifier |
| `tenant_id` | `bigint unsigned` | No | Index | Multi-tenant company identifier |
| `vehicle_use_id` | `bigint unsigned` | No | FK | Associated vehicle custody record |
| `driver_employee_id` | `bigint unsigned` | Yes | FK | Operating chauffeur employee ID |
| `reference` | `varchar(100)` | No | Unique index | Trip sheet serial / reference number |
| `chart_date` | `date` | No | Index | Operational duty date |
| `starts_at` / `ends_at` | `timestamp` | No | | Duty start and end timestamps |
| `start_odometer` | `decimal(12,2)` | No | | Duty departure odometer |
| `end_odometer` | `decimal(12,2)` | No | | Duty arrival odometer |
| `commercial_km` | `decimal(12,2)` | No | `0.00` | Billable customer business kilometers |
| `private_km` | `decimal(12,2)` | No | `0.00` | Non-billable private kilometers |
| `total_km` | `decimal(12,2)` | No | `0.00` | Total kilometers (`commercial + private`) |
| `ac_mode` | `varchar(32)` | No | `front` | `non_ac`, `front`, `dual` |
| `normal_ot_minutes` | `int unsigned` | No | `0` | Standard 1.5x overtime minutes |
| `double_ot_minutes` | `int unsigned` | No | `0` | Holiday / Weekend 2.0x overtime minutes |
| `triple_ot_minutes` | `int unsigned` | No | `0` | Midnight / Special 3.0x overtime minutes |
| `night_outs` | `int unsigned` | No | `0` | Outstation overnight stay count |
| `status` | `varchar(32)` | No | `draft` | `draft`, `finalized`, `reversed` |
| `driver_name_snapshot` | `varchar(255)`| Yes | Nullable | Chauffeur name preserved at finalization |
| `driver_reference_snapshot`| `varchar(100)`| Yes | Nullable | Chauffeur employee number snapshot |
| `driver_observation` | `text` | Yes | Nullable | Vehicle condition / mechanical notes |
| `row_version` | `int unsigned` | No | `1` | OCC version counter |

#### 5. `vehicle_rental_billing_cycles`
Two-Stage periodic invoicing lifecycle record.

| Column | Type | Nullable | Key / Default | Description |
| :--- | :--- | :---: | :---: | :--- |
| `id` | `bigint unsigned` | No | PK, Auto Inc | Unique identifier |
| `tenant_id` | `bigint unsigned` | No | Index | Multi-tenant company identifier |
| `customer_agreement_id` | `bigint unsigned` | Yes | FK | Associated customer contract |
| `owner_agreement_id` | `bigint unsigned` | Yes | FK | Associated owner contract |
| `cycle_number` | `varchar(100)` | No | Unique index | Cycle number (e.g. `CYCLE-2026-0001`) |
| `period_from` / `period_until`| `date` | No | Index | Billing cycle calendar coverage |
| `status` | `varchar(32)` | No | `draft` | `draft`, `stage1_generated`, `stage2_attached`, `finalized`, `cancelled` |
| `billing_mode` | `varchar(32)` | No | `with_running_chart`| `with_running_chart`, `without_running_chart` |
| `finalization_mode`| `varchar(32)` | No | `supplementary_invoice`| `supplementary_invoice` (Mode A), `consolidated_revision` (Mode B) |
| `base_rent_amount` | `decimal(15,6)` | No | `0.000000` | Prorated base hire rent |
| `excess_km_amount` | `decimal(15,6)` | No | `0.000000` | Usage excess distance charges |
| `overtime_amount` | `decimal(15,6)` | No | `0.000000` | Multi-tier chauffeur overtime charges |
| `night_out_amount` | `decimal(15,6)` | No | `0.000000` | Chauffeur outstation night allowances |
| `tolls_amount` | `decimal(15,6)` | No | `0.000000` | Reimbursable expressway tolls |
| `additions_amount` | `decimal(15,6)` | No | `0.000000` | Ad-hoc additions subtotal |
| `deductions_amount`| `decimal(15,6)` | No | `0.000000` | Ad-hoc deductions subtotal |
| `net_subtotal` | `decimal(15,6)` | No | `0.000000` | Net cycle subtotal before taxes |
| `tax_amount` | `decimal(15,6)` | No | `0.000000` | Total billed tax |
| `total_billed_amount`| `decimal(15,6)` | No | `0.000000` | Total billed amount across all invoices |
| `initial_invoice_id`| `bigint unsigned` | Yes | FK | Linked Stage 1 Provisional Invoice |
| `supplementary_invoice_id`| `bigint unsigned`| Yes| FK | Linked Stage 2 Mode A Supplementary Invoice |
| `final_invoice_id` | `bigint unsigned` | Yes | FK | Linked Stage 2 Mode B Consolidated Invoice |
| `row_version` | `int unsigned` | No | `1` | OCC version counter |

#### 6. `vehicle_rental_reassignments`
Immutable audit register of mid-contract driver replacements and atomic vehicle swaps.

| Column | Type | Nullable | Key / Default | Description |
| :--- | :--- | :---: | :---: | :--- |
| `id` | `bigint unsigned` | No | PK, Auto Inc | Unique identifier |
| `tenant_id` | `bigint unsigned` | No | Index | Multi-tenant company identifier |
| `reassignment_type` | `varchar(32)` | No | | `vehicle_replacement`, `driver_reassignment` |
| `customer_agreement_id`| `bigint unsigned`| Yes| FK | Associated customer contract |
| `vehicle_use_id` | `bigint unsigned` | Yes | FK | Associated vehicle custody record |
| `running_chart_id` | `bigint unsigned` | Yes | FK | Associated running chart (for driver swap) |
| `previous_vehicle_id` | `bigint unsigned` | Yes | FK | Replaced physical vehicle |
| `new_vehicle_id` | `bigint unsigned` | Yes | FK | Replacement physical vehicle |
| `previous_driver_employee_id` | `bigint unsigned` | Yes | FK | Relieved chauffeur |
| `new_driver_employee_id` | `bigint unsigned` | Yes | FK | Assigned chauffeur |
| `occurred_at` | `timestamp` | No | Index | Physical swap / handover timestamp |
| `reason` | `text` | Yes | Nullable | Reason for replacement / reassignment |
| `financial_status` | `varchar(32)` | No | `pending` | `pending`, `settled`, `waived` |
| `financial_delta_amount` | `decimal(15,6)`| No | `0.000000`| Rate differential incurred by swap |

### 2.3 Immutable History & Event Tables

Business history is strictly preserved. When an agreement, vehicle use, running chart, or incident changes state, an immutable audit record is created via the [`ImmutableRentalHistory`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Models/ImmutableRentalHistory.php) trait:

| Main Model | History Table | Captured Audit Data |
| :--- | :--- | :--- |
| `CustomerAgreement` | `vehicle_rental_customer_agreements_history` | `row_version`, `status`, complete `terms` JSON snapshot, `recorded_by`, `recorded_at`, `reason` |
| `OwnerAgreement` | `vehicle_rental_owner_agreements_history` | `row_version`, `status`, `terms` JSON snapshot, `recorded_by`, `recorded_at`, `reason` |
| `VehicleUse` | `vehicle_rental_use_history` | `row_version`, `status`, `handed_over_at`, `returned_at`, `handover_odometer`, `return_odometer`, `reason` |
| `RunningChart` | `vehicle_rental_running_chart_history` | `row_version`, `status`, `start_odometer`, `end_odometer`, `commercial_km`, `normal_ot_minutes`, `recorded_by` |
| `RentalIncident` | `vehicle_rental_incident_events` | `incident_id`, `event_type`, `old_status`, `new_status`, `comment`, `created_by` |
| `AgreementAdjustment` | `vehicle_rental_agreement_adjustments_history` | `adjustment_id`, `old_status`, `new_status`, `changed_by`, `reason` |
| `VehicleFinanceAgreement`| `vehicle_rental_finance_status_histories` | `finance_agreement_id`, `old_status`, `new_status`, `changed_by`, `reason` |

### 2.4 The Agreement Terms JSON Schema

All monetary and operational rates are maintained in the agreement's `terms` JSON column. The schema defines 12 standard decimal string keys (scaled to 6 decimal places via BCMath):

```json
{
  "base_rate": "150000.000000",
  "included_km": "3000.000000",
  "excess_km_rate": "45.000000",
  "non_ac_rate": "0.000000",
  "front_ac_rate": "48.000000",
  "dual_ac_rate": "52.000000",
  "driver_rate": "35000.000000",
  "normal_ot_rate": "450.000000",
  "double_ot_rate": "600.000000",
  "triple_ot_rate": "900.000000",
  "night_out_rate": "2500.000000",
  "deposit_requirement": "50000.000000"
}
```

*Crucial Business Rule:* `deposit_requirement` is permitted **strictly on Customer Agreements**. Attempting to pass `deposit_requirement` on an Owner Agreement triggers a `ValidationException`.

### 2.5 Complete Domain Enums Catalog (38 Enums)

The module enforces strict PHP 8.2 backed enums across all domain transitions:

| Enum Name | Backed Type | Values | Domain Purpose |
| :--- | :---: | :--- | :--- |
| `AgreementAction` | `string` | `activate`, `close` | Lifecycle transitions for agreements |
| `AgreementAdjustmentCategory` | `string` | `rate_correction`, `penalty`, `rebate`, `compensation`, `other` | Contractual adjustment classifications |
| `AgreementAdjustmentStatus` | `string` | `draft`, `authorized`, `applied`, `cancelled` | Adjustment lifecycle |
| `AgreementAdjustmentType` | `string` | `debit_note`, `credit_note` | Financial direction of adjustment |
| `AgreementKind` | `string` | `customer`, `owner` | Decoupled commercial track identifier |
| `AgreementStatus` | `string` | `draft`, `active`, `closed` | Core agreement contract states |
| `AirConditioningMode` | `string` | `non_ac`, `front`, `dual` | Climate control mode for differential rates |
| `BaseChargeSource` | `string` | `schedule`, `manual`, `billing_cycle` | Provenance of base hire charge |
| `BaseRentPolicy` | `string` | `advance`, `arrears` | Timing policy for provisional rent |
| `BillingCycleItemCategory` | `string` | `fuel_differential`, `driver_salary`, `toll_reimbursement`, `damage_charge`, `downtime_rebate`, `other` | Ad-hoc addition/deduction items |
| `BillingCycleItemType` | `string` | `addition`, `deduction` | Mathematical effect on billing subtotal |
| `BillingCycleMode` | `string` | `with_running_chart`, `without_running_chart` | Operational telemetry attachment model |
| `BillingCycleStatus` | `string` | `draft`, `stage1_generated`, `stage2_attached`, `finalized`, `cancelled` | Master Two-Stage Invoicing lifecycle |
| `BillingFinalizationMode` | `string` | `supplementary_invoice`, `consolidated_revision` | Mode A (Supplementary) vs Mode B (Consolidated) |
| `DriverIdentitySource` | `string` | `employee`, `external`, `snapshot` | Provenance of driver name & code |
| `DriverMode` | `string` | `with_driver`, `self_drive` | Chauffeur-driven vs self-drive hire |
| `IncidentPermission` | `string` | `view`, `record`, `review` | Permission mapping keys |
| `IncidentReviewAction` | `string` | `confirm`, `reject` | Investigation approval transitions |
| `IncidentStatus` | `string` | `reported`, `under_investigation`, `insurance_claimed`, `resolved`, `closed` | Incident resolution pipeline |
| `IncidentType` | `string` | `accident`, `breakdown`, `theft`, `traffic_violation`, `customer_complaint`, `other` | Physical fleet incident categories |
| `MileagePolicy` | `string` | `included_allowance`, `unlimited`, `per_km_only` | Distance consumption policy |
| `ReassignmentFinancialStatus` | `string` | `pending`, `settled`, `waived` | Financial settlement of swap differential |
| `ReassignmentType` | `string` | `vehicle_replacement`, `driver_reassignment` | Fleet mid-contract reallocation type |
| `RentalBasis` | `string` | `daily`, `weekly`, `monthly`, `calendar_month` | Periodic recurrence basis |
| `RentalExpenseAllocationStatus` | `string` | `pending`, `confirmed`, `invoiced`, `reversed` | Expense allocation lifecycle |
| `RentalExpenseAllocationType` | `string` | `company_fleet`, `customer_recovery`, `owner_deduction`, `shared` | 4-Way fleet expense distribution matrix |
| `RentalExpenseStatus` | `string` | `draft`, `submitted`, `approved`, `rejected`, `allocated`, `reversed` | Operating expense authorization state |
| `RentalExpenseType` | `string` | `fuel`, `toll`, `repair`, `maintenance`, `insurance`, `fine`, `other` | Fleet expense operational categories |
| `RentalReservationStatus` | `string` | `draft`, `pending`, `confirmed`, `converted`, `cancelled`, `expired` | Booking inquiry state machine |
| `RunningChartAction` | `string` | `finalize`, `reverse` | Daily trip log lifecycle actions |
| `RunningChartStatus` | `string` | `draft`, `finalized`, `reversed` | Daily trip log state machine |
| `UsageChargeComponent` | `string` | `excess_mileage`, `overtime_normal`, `overtime_double`, `overtime_triple`, `night_out`, `toll` | Component item types for usage |
| `UsageChargePolicy` | `string` | `tier_standard`, `tier_express`, `custom` | Telematics rate assessment policy |
| `UsageChargeSource` | `string` | `running_chart`, `manual_assessment` | Origin of usage charge data |
| `VehicleFinanceAgreementStatus` | `string` | `draft`, `active`, `settled`, `cancelled` | Bank leasing contract states |
| `VehicleFinanceInstallmentStatus`| `string` | `pending`, `payable_created`, `paid`, `defaulted`| Amortization schedule status |
| `VehicleUseAction` | `string` | `handover`, `return`, `cancel` | Custody transition actions |
| `VehicleUseStatus` | `string` | `planned`, `in_custody`, `returned`, `cancelled` | Physical custody state machine |

---

## 3. Subsystems, State Machines & Operational Lifecycles

### 3.1 Rental Reservations & Booking Inquiries

The reservation subsystem handles customer inquiries, rate quotes, and vehicle holds prior to contract signing.

```mermaid
stateDiagram-v2
    [*] --> Draft: User creates booking inquiry
    Draft --> Pending: Submitted for availability check
    Draft --> Confirmed: Manager approves rate & hold
    Pending --> Confirmed: Vehicle & dates secured
    Draft --> Cancelled: Customer withdraws
    Pending --> Cancelled: Customer cancels
    Pending --> Expired: Cutoff window lapses
    Confirmed --> Converted: convertToAgreement()
    Confirmed --> Cancelled: Customer cancels
    Converted --> [*]
    Cancelled --> [*]
    Expired --> [*]
```

- **Service:** [`RentalReservationService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/RentalReservationService.php)
- **Controller:** [`RentalReservationController.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Http/Controllers/RentalReservationController.php)

#### CRUD Payloads & Transitions:
- **Create Reservation (`POST /api/v1/vehicle-rental/reservations`):**
```json
{
  "customer_id": 14,
  "vehicle_id": 8,
  "starts_on": "2026-12-01",
  "ends_on": "2026-12-15",
  "rental_basis": "daily",
  "agreed_daily_rate": "8500.000000",
  "agreed_deposit_amount": "25000.000000",
  "notes": "VIP Corporate Guest Airport Shuttle"
}
```
- **Convert to Customer Agreement (`POST /api/v1/vehicle-rental/reservations/12/convert`):**
  - Atomically creates an active `CustomerAgreement` copying rates and dates.
  - Returns the newly created agreement object and updates reservation status to `converted`.

### 3.2 Customer Commercial Agreements & Successor Lineage

Customer agreements govern the commercial relationship, base hire rates, excess rates, and billing basis.

```mermaid
stateDiagram-v2
    [*] --> Draft: Create agreement
    Draft --> Draft: Update terms / details
    Draft --> Active: POST /{id}/activate
    Active --> Active: Assign vehicles / Create billing cycles
    Active --> Active: POST /{id}/successor (Create successor)
    Active --> Closed: POST /{id}/close
    Closed --> [*]
```

- **Service:** [`AgreementService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/AgreementService.php)
- **Controller:** [`AgreementController.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Http/Controllers/AgreementController.php)

#### Contract Successor Lineage:
When renegotiating rates or renewing expiring agreements:
1. Operator calls `POST /api/v1/vehicle-rental/customer/agreements/15/successor`.
2. System creates a Draft successor agreement linked to `predecessor_agreement_id = 15`.
3. Predecessor contract remains active and continues billing without disruption.
4. When the successor is activated (`POST .../activate`), the predecessor agreement is atomically closed on the exact boundary date (`closed_at`), ensuring seamless legal continuity without gaps.

### 3.3 Vehicle Owner Lease Agreements & Sourcing

Owner agreements represent the sourcing supply contracts for vehicles leased from external suppliers.
- **Service:** [`AgreementService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/AgreementService.php)
- **Validation Rule:** Owner agreements must define `vehicle_id`. The vehicle registration and ownership snapshots are permanently locked.
- **Payload Example (`POST /api/v1/vehicle-rental/owner/agreements`):**
```json
{
  "supplier_id": 22,
  "vehicle_id": 14,
  "reference": "OWN-2026-0042",
  "starts_on": "2026-11-01",
  "ends_on": "2027-10-31",
  "rental_basis": "monthly",
  "terms": {
    "base_rate": "120000.000000",
    "included_km": "2500.000000",
    "excess_km_rate": "38.000000",
    "normal_ot_rate": "350.000000",
    "double_ot_rate": "500.000000",
    "triple_ot_rate": "750.000000",
    "night_out_rate": "2000.000000"
  }
}
```

### 3.4 Vehicle Physical Custody (`VehicleUse`)

`VehicleUse` represents the real-world physical custody of a vehicle assigned to a customer agreement.

```mermaid
stateDiagram-v2
    [*] --> Planned: User plans assignment
    Planned --> InCustody: POST /{id}/handover (Key Handover)
    Planned --> Cancelled: POST /{id}/cancel
    InCustody --> Returned: POST /{id}/return (Vehicle Check-in)
    InCustody --> Returned: POST /{id}/replace (Atomic Mid-Lease Swap)
    Returned --> [*]
```

- **Service:** [`VehicleUseService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/VehicleUseService.php)
- **Controller:** [`VehicleUseController.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Http/Controllers/VehicleUseController.php)

#### Key Operational Validations:
1. **Clock Drift Tolerance:** Validates that `occurred_at` does not exceed 10 minutes into the future (`CLOCK_DRIFT_TOLERANCE_MINUTES`).
2. **Odometer Continuity:** `handover_odometer` must be equal to or greater than the vehicle's last recorded odometer reading.
3. **Return Integrity:** `returned_at` must be strictly after `handed_over_at`, and `return_odometer >= handover_odometer`. It verifies that no finalized running charts have odometers exceeding the return odometer.

### 3.5 Mid-Lease Atomic Vehicle Swaps (Replacements)

When a vehicle breaks down, requires maintenance, or is recalled during an active lease:
- The operator calls `POST /api/v1/vehicle-rental/vehicle-uses/{id}/replace`.
- In a single atomic database transaction:
  1. Closes existing custody (`Returned`) at `replacement_time` with `return_odometer`.
  2. Creates replacement custody use record with `replaces_use_id = $oldUse->id`, `status = InCustody`, and `handed_over_at = replacement_time`.
  3. Records entry in `vehicle_rental_reassignments` with `reassignment_type = vehicle_replacement`.
  4. The Two-Stage Invoicing engine prorates base rent across both vehicles based on exact physical custody days without double-counting the swap date.

### 3.6 Driver Assignments & Reassignments

Drivers are assigned to vehicle uses or individual daily running charts.
- **Service:** [`VehicleDriverReassignmentService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/VehicleDriverReassignmentService.php)
- **Controller:** [`VehicleDriverReassignmentController.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Http/Controllers/VehicleDriverReassignmentController.php)
- **Running Chart Driver Reassignment (`POST /api/v1/vehicle-rental/running-charts/{chart}/reassign-driver`):**
  - Asserts chart is not locked in a finalized billing cycle.
  - Verifies new chauffeur has no overlapping duty shifts on that date.
  - Updates snapshots (`driver_employee_id`, `driver_name_snapshot`, `driver_reference_snapshot`).
  - Writes a traceable record in `vehicle_rental_reassignments`.

### 3.7 Daily Running Charts & Telematics Tracking

Running charts are daily trip manifests recording real-world vehicle duty.
- **Service:** [`RunningChartService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/RunningChartService.php)
- **Controller:** [`RunningChartController.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Http/Controllers/RunningChartController.php)

#### Metrics Tracked:
- `start_odometer` and `end_odometer` (must satisfy `end_odometer >= start_odometer`)
- `commercial_km` + `private_km` (asserts `commercial_km + private_km === total_km`)
- `normal_ot_minutes` (Standard 1.5x overtime)
- `double_ot_minutes` (Sunday / Poya / Holiday 2.0x overtime)
- `triple_ot_minutes` (Midnight / Special holiday 3.0x overtime)
- `night_outs` (Driver overnight outstation allowances)
- `ac_mode` (`non_ac`, `front`, `dual`)
- **Immutability:** Finalized charts cannot be edited or deleted. Errors are corrected by transitioning to `Reversed` and logging a replacement chart.

### 3.8 Fleet Operating Expenses & 4-Way Allocation Matrix

Fleet expenses (fuel, highway tolls, replacement tires, servicing) are logged in `vehicle_rental_expenses`.
- **Service:** [`RentalExpenseService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/RentalExpenseService.php)
- **The 4-Way Allocation Matrix:**
  1. `company_fleet`: Tenant company absorbs the expense as operating overhead.
  2. `customer_recovery`: Expense is billed to the customer (e.g. expressway tolls, damaged tire replacement) via a Sales Invoice or Debit Note.
  3. `owner_deduction`: Expense is deducted from the vehicle owner's monthly settlement bill (e.g. routine mechanical servicing performed on a lessor vehicle).
  4. `shared`: Proportional split between tenant and customer/owner.

### 3.9 Damage Incidents & Private Proofs

Accidents and damage events require legal and insurance documentation.
- **Service:** [`RentalIncidentService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/RentalIncidentService.php)
- **Attachments Service:** [`RentalIncidentAttachmentService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/RentalIncidentAttachmentService.php)
- **Evidence Storage:** Photos, police accident reports, and third-party repair estimates are uploaded via `RentalIncidentAttachmentController.php` to private tenant-scoped storage.
- **Financial Rule:** Incidents do not invent money. Repair bills and insurance recoveries are created through `AgreementAdjustment`, `BillingCycleItem` (Damage Charge), or `RentalExpense` customer recovery allocations.

### 3.10 Vehicle Finance & Bank Leasing Contracts

For fleet vehicles financed via bank loans or hire-purchase leases:
- **Service:** [`VehicleFinanceService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/VehicleFinanceService.php)
- **Controller:** [`VehicleFinanceController.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Http/Controllers/VehicleFinanceController.php)
- Generates amortization schedules (`vehicle_rental_finance_installments`) dividing monthly installments into principal, interest, and finance charges.
- Calling `POST /api/v1/vehicle-rental/finance-agreements/installments/{id}/payable` generates an authentic AP Purchase Invoice in `Modules\Invoice` payable to the bank.

### 3.11 Agreement Financial Adjustments

Ad-hoc contractual revisions (rate adjustments, early termination penalties, goodwill discounts) are managed via `vehicle_rental_agreement_adjustments`.
- **Controller:** [`AgreementAdjustmentController.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Http/Controllers/AgreementAdjustmentController.php)
- **Lifecycle:** `Draft` -> `Authorized` -> `Applied` (linked to an invoice or owner settlement bill) -> `Cancelled`.
- **Restoration Safety:** If an invoice or owner bill containing an adjustment is cancelled, the adjustment status is restored back to `Authorized` and unlinked from the invoice, preventing stranded adjustments.

---

## 4. The Two-Stage Invoicing Engine

The **Two-Stage Invoicing Engine** ([`TwoStageInvoicingService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/TwoStageInvoicingService.php)) is the commercial billing centerpiece of the module.

### 4.1 Why Two-Stage Invoicing?

In corporate vehicle hire, monthly billing occurs in two distinct operational waves:
1. **At Month Start (Provisional Wave):** The corporate client requires a tax invoice on the 1st of the month for the agreed monthly **Base Rent** so they can process payment in advance.
2. **At Month End (Supplementary Wave):** The rental company collects all physical trip sheets (running charts), tallies actual kilometers, driver overtime hours, night-out allowances, and highway tolls, and issues a **Supplementary Usage Invoice** for the delta.

```
Month Start (Day 1)                                           Month End (Day 30/31)
========================================================================================
[ Stage 1: Provisional Invoicing ]                           [ Stage 2: Supplementary ]
Customer Agreement Terms                                     Physical Running Charts
  └─ Monthly Base Rent: $150,000                               └─ Excess KM: 420 km @ $45
                                                               └─ Overtime: 14 hrs @ $450
                                                               └─ Night Outs: 2 nights
                                                               └─ Tolls: $3,400
                                                               └─ Rebates / Deductions
                                                             ---------------------------
Tax Invoice #INV-2026-001 Issued                             Supplementary Invoice #INV-2026-002
Grand Total: $150,000 + Tax                                  Delta Usage: $31,500 + Tax
```

### 4.2 Stage 1: Advance / Provisional Base Rent Invoicing

When creating a billing cycle via `POST /api/v1/vehicle-rental/{kind}/agreements/{id}/billing-cycles` with `issue_initial_invoice = true`:
1. **Base Rent Preview:** Calculates the base rent for the period (`BaseRentPreview::calculate()`).
2. **Custody Proration:** For agreements where multiple vehicles were assigned during the cycle (or mid-month swaps took place):
   - Computes physical custody days for each vehicle using actual handover and return timestamps.
   - Accurately prorates base rent across vehicles:
     $$\text{Prorated Base Rent} = \frac{\text{Agreement Base Rate} \times \text{Vehicle Custody Days}}{\text{Total Cycle Custody Days}}$$
   - Generates itemized line items per vehicle: `Base Rental Fee · CAS-4912 · 18 Days (01/10/2026 To 18/10/2026)`.
3. **Structured Reference Fields:** Attaches purchaser reference metadata (`Agreement Reference`, `Billing Period`, `Assigned Vehicles`) for official tax document printouts.
4. **Invoice Creation:** Calls `InvoiceCreationService::create()` via `TaxedSourceInvoiceFactory`. Sets cycle status to `Stage1Generated`.

### 4.3 Running Chart Attachment & Operational Rate Calculations

When trip sheets are finalized, the operator calls `POST /api/v1/vehicle-rental/billing-cycles/{id}/charts`:
1. **Running Chart Validation:** Validates that each chart belongs to the agreement, is in `Finalized` status, and is not already locked in another finalized cycle.
2. **Operational Tallying:**
   - **Excess Kilometers:**
     $$\text{Excess KM} = \max(0, \text{Total Commercial KM} - \text{Included Free KM})$$
     $$\text{Excess KM Amount} = \text{Excess KM} \times \text{Excess KM Rate}$$
   - **Multi-Tier Overtime:**
     $$\text{Normal OT Amount} = \frac{\text{Normal OT Minutes}}{60} \times \text{Normal OT Rate}$$
     $$\text{Double OT Amount} = \frac{\text{Double OT Minutes}}{60} \times \text{Double OT Rate}$$
     $$\text{Triple OT Amount} = \frac{\text{Triple OT Minutes}}{60} \times \text{Triple OT Rate}$$
   - **Night-Out Allowances:** $\text{Total Nights} \times \text{Night-Out Rate}$
   - **Air Conditioning Differentials:** If running charts specify `front` or `dual` AC, and AC rates exceed the baseline rate, differential charge items are generated.
   - **Highway Tolls:** Aggregates approved toll expenses linked to the running charts and vehicle uses.
3. **Cycle Recalculation:** Advances cycle status to `Stage2Attached`.

### 4.4 Stage 2: Supplementary Usage Invoicing (Mode A)

When finalizing the cycle in Mode A (`supplementary_invoice`):
1. **Delta Calculation:**
   $$\text{Delta Subtotal} = \text{Cycle Net Subtotal} - \text{Stage 1 Base Rent Amount}$$
2. **Itemization:** Emits discrete charge lines for:
   - Excess Mileage Charges with rate breakdown
   - Normal Overtime (1.5x), Double Overtime (2.0x), and Triple Overtime (3.0x)
   - Driver Night-Out Allowances
   - Expressway Tolls
   - Ad-hoc additions
3. **Line-Level Deduction Mapping (`applyDeductionsAsDiscounts`):**
   - Financial accounting rules strictly forbid negative line unit prices (`unit_price < 0`).
   - Contractual deductions, downtime credits, and goodwill rebates are allocated proportionally as **line discounts** (`discountAmount`) across the charge lines.
   - Guarantees exact mathematical reconciliation between gross itemized charges, discounts, and net invoice subtotal.

### 4.5 Negative Delta Handling & Credit Notes

If a vehicle suffered substantial downtime during the month and contractual rebates exceed the usage charges:
- $\text{Delta Subtotal} < 0$
- Rather than throwing an error or generating a corrupt negative invoice, the engine automatically issues an authentic **Credit Note Invoice** (`InvoiceType::Credit`) for the absolute delta amount ($\text{abs}(\text{Delta Subtotal})$).
- Emits itemized deduction lines on the credit note.
- If the Stage 1 invoice has an unpaid remaining balance, calls `InvoiceBalanceUpdater::allocateCredit()` to automatically offset the credit note against the open Stage 1 invoice!

### 4.6 Consolidated Revision Invoicing (Mode B)

Certain corporate lessees refuse multiple invoices and demand a single consolidated invoice at month end:
1. Operator selects `finalization_mode = 'consolidated_revision'`.
2. Generates a new comprehensive invoice (`final_invoice_id`) containing the prorated base rent and all itemized operational usage charges.
3. Automatically transitions the consolidated invoice to `Approved` and `Posted`.
4. If a Stage 1 invoice was previously issued:
   - Cancels the Stage 1 invoice.
   - Calls `PaymentAllocationReversalService` to transfer any customer payments or deposits tendered on Stage 1 over to the new consolidated invoice!

### 4.7 Cancellation & Source Restoration Engine

If an operator cancels an issued rental invoice via `POST /api/v1/vehicle-rental/billing/documents/{id}/cancel`:
- [`RentalBillingDocumentService::cancelDocument()`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/RentalBillingDocumentService.php) executes full bidirectional restoration:
  1. Cancels the invoice via `InvoiceStatusService::cancel()`.
  2. If cancelling a **Supplementary Invoice (Mode A)**:
     - Resets the billing cycle status back to `Stage2Attached`.
     - Clears `supplementary_invoice_id`, `finalized_at`, and `finalized_by`.
     - Restores `total_billed_amount` back to the Stage 1 baseline.
  3. If cancelling a **Stage 1 Invoice**:
     - Resets billing cycle status back to `Draft`.
     - Clears `initial_invoice_id`.
  4. If cancelling an **Owner Settlement Bill**:
     - Restores all included `AgreementAdjustment` records back to `Authorized` with `invoice_id = null`.
     - Unlinks `RentalExpenseAllocation` maintenance chargebacks so they can be re-settled.
     - Restores unbilled owner billing cycles back to `Stage2Attached`/`Draft`.

### 4.8 Complete End-to-End Invoicing Numeric Case Study

To see how the calculations work in practice, let us follow a complete 30-day corporate rental cycle:

#### Contract Setup:
- **Customer:** Ceylon Logistics Ltd
- **Agreement Reference:** `AGR-2026-COL-008`
- **Rental Period:** October 1, 2026 to October 31, 2026 (31 Days)
- **Agreed Terms:**
  - Base Hire Rate: LKR 150,000.00 / month
  - Included Free Kilometers: 3,000.00 km
  - Excess Kilometer Rate: LKR 45.00 / km
  - Normal Overtime (1.5x): LKR 450.00 / hour
  - Double Overtime (2.0x): LKR 600.00 / hour
  - Triple Overtime (3.0x): LKR 900.00 / hour
  - Night Out Allowance: LKR 2,500.00 / night
  - VAT: 15% Standard Rated

#### Step 1: Day 1 — Stage 1 Provisional Invoicing
- System calculates 31 days base rent: `LKR 150,000.00`.
- Issues Tax Invoice `#INV-2026-0891`:
  - Line 1: `Base Rental Fee · CAS-4912 · 31 Days` = LKR 150,000.00
  - 15% VAT: LKR 22,500.00
  - **Grand Total:** LKR 172,500.00 (Customer pays LKR 172,500.00 on Oct 5 via Cheque).

#### Step 2: Day 18 — Mid-Lease Vehicle Swap
- Vehicle `CAS-4912` suffers an alternator failure.
- Operator executes atomic swap: `CAS-4912` returned at 10:00 AM; replacement vehicle `CAB-1234` handed over immediately.
- Custody Days Breakdown:
  - `CAS-4912`: Day 1 to Day 18 = 18 Days
  - `CAB-1234`: Day 18 to Day 31 = 13 Days (swap date boundary not double-counted)
  - Prorated Base Rent for `CAS-4912`: $(150,000 \times 18) / 31 = \text{LKR } 87,096.77$
  - Prorated Base Rent for `CAB-1234`: $(150,000 \times 13) / 31 = \text{LKR } 62,903.23$

#### Step 3: Day 31 — Running Chart Telematics Tally
At month end, 31 running charts are collected and attached to the cycle:
- **Distance:**
  - Total Commercial Kilometers: 3,420.00 km
  - Included Allowance: 3,000.00 km
  - Excess Kilometers: $3,420 - 3,000 = 420.00\text{ km}$
  - Excess KM Charge: $420 \times 45.00 = \text{LKR } 18,900.00$
- **Overtime:**
  - Normal OT: 14 hours = $14 \times 450.00 = \text{LKR } 6,300.00$
  - Double OT: 5 hours = $5 \times 600.00 = \text{LKR } 3,000.00$
  - Triple OT: 1 hour = $1 \times 900.00 = \text{LKR } 900.00$
  - Total OT Amount: LKR 10,200.00
- **Outstation Allowances:**
  - 2 Night Outs: $2 \times 2,500.00 = \text{LKR } 5,000.00$
- **Tolls Reimbursement:**
  - Expressway receipts logged: LKR 3,400.00
- **Contractual Downtime Deduction:**
  - Alternator breakdown goodwill rebate: LKR -6,000.00 (Item category `downtime_rebate`)

#### Step 4: Final Calculations (Mode A)
- Gross Supplementary Usage:
  $$\text{Gross Usage} = 18,900 + 10,200 + 5,000 + 3,400 = \text{LKR } 37,500.00$$
- Net Delta Subtotal:
  $$\text{Net Delta} = 37,500.00 - 6,000.00 = \text{LKR } 31,500.00$$
- Line-Level Deduction Mapping (`applyDeductionsAsDiscounts`):
  - Downtime rebate of LKR 6,000 is distributed across the 4 charge lines as line discounts.
  - Line 1 (Excess KM): LKR 18,900.00 gross - LKR 3,024.00 discount = LKR 15,876.00
  - Line 2 (Overtime): LKR 10,200.00 gross - LKR 1,632.00 discount = LKR 8,568.00
  - Line 3 (Night Outs): LKR 5,000.00 gross - LKR 800.00 discount = LKR 4,200.00
  - Line 4 (Tolls): LKR 3,400.00 gross - LKR 544.00 discount = LKR 2,856.00
  - Net Subtotal: LKR 31,500.00
- 15% VAT on Delta: LKR 4,725.00
- **Supplementary Tax Invoice Issued (`#INV-2026-0955`):** Grand Total = **LKR 36,225.00**.

---

## 5. Owner Settlement AP Billing & Disbursements

The vehicle owner settlement subsystem ([`OwnerSettlementBillingService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/OwnerSettlementBillingService.php)) governs the payments owed to external vehicle owners (lessors).

### 5.1 Commercial Workflow of Fleet Sourcing

```
Vehicle Owner (Lessor / Supplier)
   │
   ├─► 1. Base Lease Rent (Fixed Monthly Hire)
   ├─► 2. Operational Mileage & Usage Earnings
   │
   ├─▼ LESS DEDUCTIONS:
   │    ├─► 3. Statutory 10% Withholding Tax (WHT)
   │    ├─► 4. Fleet Maintenance Chargebacks (Repairs paid by AutoERP)
   │    └─► 5. Contractual Penalties / Damage Rebates
   │
   └─► = Net Settlement Payable (AP Supplier Bill)
            │
            └─► Disbursed via Cheque / Bank Transfer
```

### 5.2 Gross Lease Earnings & Operational Usage Reimbursement

When generating an owner settlement bill via `POST /api/v1/vehicle-rental/owner-settlement-bill`:
1. Queries scheduled `OwnerBaseCharge` and `OwnerUsageCharge` records, or unbilled owner `BillingCycle` records covering the settlement date range.
2. Emits discrete line items for:
   - Gross Lease Hire: `Gross Vehicle Lease Rent · {AgreementRef} · {VehicleNo} ({Period})`
   - Operational Usage & Overtime Earnings
   - Authorized owner debit note adjustments

### 5.3 Statutory 10% Withholding Tax (WHT :3305-000)

Under national tax laws (e.g. Sri Lanka Inland Revenue Act), payments to lessors for motor vehicle hire are subject to mandatory **10% Withholding Tax**:
- The service calculates:
  $$\text{WHT Amount} = \frac{\text{Gross Lease Payable} \times 10.00}{100.00}$$
- Recorded as an authoritative `InvoiceAdjustmentData` with `AdjustmentType::Withholding` and `AdjustmentEffect::Decrease`.
- In the General Ledger posting plan, this credits the statutory withholding liability account (**Account `:3305-000 Withholding Tax Payable`**), properly reducing the net cash payable to the lessor while retaining the full gross lease expense.

### 5.4 Fleet Maintenance Chargeback Deductions

When AutoERP's workshop or a third-party garage performs maintenance on an owner's vehicle (e.g. oil changes, brake pads, tire replacements):
1. An expense is logged in `RentalExpense` and allocated as `RentalExpenseAllocationType::OwnerDeduction`.
2. The settlement engine queries all confirmed, uninvoiced maintenance allocations targeting the owner agreement.
3. Inserts an adjustment of `AdjustmentType::Discount` titled `Fleet Maintenance Deductions`.

### 5.5 Deduction Capping Safeguard

To prevent negative supplier bills or over-deducting beyond payable earnings:
$$\text{Max Deductions Capacity} = \max(0, \text{Gross Payable} - \text{WHT Amount})$$
$$\text{Applicable Maintenance} = \min(\text{Maintenance Deductions}, \text{Max Deductions Capacity})$$
$$\text{Remaining Capacity} = \text{Max Deductions Capacity} - \text{Applicable Maintenance}$$
$$\text{Applicable Adjustments} = \min(\text{Adjustment Deductions}, \text{Remaining Capacity})$$
$$\text{Total Deductions} = \text{WHT Amount} + \text{Applicable Maintenance} + \text{Applicable Adjustments}$$
$$\text{Net Payable} = \text{Gross Payable} - \text{Total Deductions}$$

### 5.6 Settlement Cancellation & Complete Restoration

If a draft or approved owner settlement bill is cancelled:
- Unlinks all `RentalExpenseAllocation` chargebacks (`invoice_id = null`).
- Restores all `AgreementAdjustment` records back to `Authorized` status (`invoice_id = null`), increments `row_version`, and logs an audit history entry.
- Restores any unbilled owner billing cycles back to `Stage2Attached`.

### 5.7 Owner Settlement Numeric Case Study

- **Lessor (Supplier):** Sunil Perera
- **Owner Agreement:** `OWN-2026-COL-004` (Vehicle: `CAS-4912`)
- **Period:** October 1, 2026 to October 31, 2026
- **Earnings:**
  - Agreed Monthly Base Lease Hire: LKR 120,000.00
  - Overtime Driver Earnings Reimbursement: LKR 6,000.00
  - **Gross Payable:** LKR 126,000.00
- **Deductions:**
  - Statutory 10% WHT: $126,000 \times 10\% = \text{LKR } 12,600.00$
  - Max Deductions Capacity: $126,000 - 12,600 = \text{LKR } 113,400.00$
  - Fleet Maintenance Chargeback (New brake pads & service invoice): LKR 8,000.00 (within capacity)
  - Total Deductions: $12,600 + 8,000 = \text{LKR } 20,600.00$
- **Net AP Settlement Bill Issued (`#BILL-2026-0312`):**
  - Gross Lease Expense: LKR 126,000.00
  - Less WHT Liability: -LKR 12,600.00
  - Less Maintenance Recovery: -LKR 8,000.00
  - **Net Cash Payable to Owner:** **LKR 105,400.00**.

---

## 6. Multi-Agreement Financial Settlements & Payments

Vehicle rental clients (corporate fleet accounts) and vehicle suppliers (fleet leasing companies) routinely manage multiple simultaneous contracts. The payment subsystem ([`RentalPaymentService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/RentalPaymentService.php)) provides comprehensive multi-agreement financial settlement.

### 6.1 Single Agreement Payments vs Party-Wide Settlements

| Feature | Single Agreement Payment | Party-Wide Multi-Agreement Settlement |
| :--- | :--- | :--- |
| **API Endpoint** | `POST .../{kind}/agreements/{id}/payments` | `POST .../{kind}/parties/{partyId}/payments` |
| **Scope** | Restricted to invoices of one agreement | Settle invoices across ALL agreements of the customer/owner |
| **Tender Allocation** | Single invoice or unapplied advance | Interactive allocation grid across multiple agreements |
| **Receipt Generation** | Displays single agreement reference | Extracts distinct references: `AGR-2026-001, AGR-2026-004` |
| **Advance Management**| Holds advance on single agreement | Draws from party-wide unapplied advances to pay open bills |

### 6.2 Interactive Document Allocation Grid

When receiving a lump-sum payment (e.g. a corporate customer pays LKR 500,000 against 5 different leased vehicles):
1. Frontend calls `GET /api/v1/vehicle-rental/{kind}/parties/{partyId}/open-documents`.
2. The endpoint returns:
   - All active agreements for the party.
   - All open invoices (`balance_due > 0`) enriched with vehicle registration, driver name, agreement reference, and period.
3. The UI presents an interactive **Document Allocation Grid**:
   - Operator enters the total tendered amount.
   - Operator clicks "Pay Full" per invoice or "Pay Full Balance On All".
   - The grid provides real-time calculations: Total Tendered, Total Allocated, Unapplied Advance / Surplus, and Balance After Payment.
4. Calling `POST /api/v1/vehicle-rental/{kind}/parties/{partyId}/payments` posts the receipt and executes atomic allocations in `Modules\Payment`.

#### Multi-Agreement Tender Payload Example:
```json
{
  "amount": "500000.000000",
  "payment_date": "2026-11-15",
  "payment_method_id": 2,
  "reference": "CHQ-899120",
  "notes": "Consolidated payment for November fleet hire",
  "allocations": [
    { "invoice_id": 104, "amount": "172500.000000" },
    { "invoice_id": 105, "amount": "172500.000000" },
    { "invoice_id": 106, "amount": "115000.000000" }
  ]
}
```
*Result:* LKR 460,000.00 is allocated across invoices #104, #105, #106. The remaining LKR 40,000.00 is preserved as an **Unapplied Advance**.

### 6.3 Unapplied Advances & Surplus Pooling

If a customer tenders more than the open balance, the surplus is preserved as an **Unapplied Advance**:
- The advance is tracked at the party level.
- Later, when new billing cycles generate invoices, an operator can call `POST /api/v1/vehicle-rental/{kind}/parties/{partyId}/payments/{paymentId}/allocate` to draw from the unapplied advance and extinguish the new invoices without requiring new cash receipts.
- **Accurate Financial Summaries:** [`AgreementFinancialSummaryService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/AgreementFinancialSummaryService.php) ensures party-wide advances are reported cleanly without double-counting against every individual agreement.

### 6.4 Payment Reversals & Refunds

- **Reversals:** Calling `POST .../payments/{id}/reverse` reverses the payment, restores the open balance on all allocated invoices, and posts reversal journal entries in the General Ledger.
- **Refunds:** Calling `POST .../payments/{id}/refund` issues an authentic refund voucher against unapplied advance balances.

---

## 7. Financial Ledger Integrations & Accounting Impact

### 7.1 Integration with `Modules\Invoice`

`VehicleRental` does not directly insert raw invoice database rows. It leverages `Modules\Invoice`:
- **Source Factory:** All invoices are constructed via [`TaxedSourceInvoiceFactory::prepare()`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Services/TaxedSourceInvoiceFactory.php).
- **Posting Profiles:** Uses `FinancePostingProfileCode::CustomerRentalInvoice` (for AR) and `FinancePostingProfileCode::OwnerRentalSettlement` (for AP).
- **Source Traceability:** Links `source_type = 'vehicle_rental_billing_cycle'` or `'vehicle_rental_owner_settlement'`, maintaining absolute bidirectional auditability.

### 7.2 Integration with `Modules\Payment`

- All cash receipts, bank transfers, and cheque disbursements are processed through [`Modules\Payment\Services\PaymentCreationService`](file:///d:/project-new/AutoERP/app/Modules/Payment/Services/PaymentCreationService.php) and [`PaymentAllocationService`](file:///d:/project-new/AutoERP/app/Modules/Payment/Services/PaymentAllocationService.php).
- Draft receipts and disbursements can be updated or cancelled prior to posting via [`PaymentUpdateService`](file:///d:/project-new/AutoERP/app/Modules/Payment/Services/PaymentUpdateService.php) and [`PaymentDraftDeletionService`](file:///d:/project-new/AutoERP/app/Modules/Payment/Services/PaymentDraftDeletionService.php).

### 7.3 Complete General Ledger Double-Entry Posting Matrix

Below are the exact standard GL journal entries generated across all 8 commercial events:

| Commercial Event | Accounting Track | Debit Account (DR) | Credit Account (CR) | Posting Profile |
| :--- | :--- | :--- | :--- | :--- |
| **Stage 1 Customer Base Rent Invoice** | Customer AR | `:1200-000` Accounts Receivable | `:4100-000` Base Rental Revenue<br/>`:2200-000` Output VAT Payable | `CustomerRentalInvoice` |
| **Stage 2 Customer Supplementary Invoice** | Customer AR | `:1200-000` Accounts Receivable | `:4110-000` Usage Revenue (KM & OT)<br/>`:4120-000` Toll Recovery<br/>`:2200-000` Output VAT Payable | `CustomerRentalInvoice` |
| **Customer Credit Note (Negative Delta)** | Customer AR Credit | `:4100-000` Base Rental Revenue<br/>`:2200-000` Output VAT Payable | `:1200-000` Accounts Receivable | `CustomerRentalInvoice` |
| **Customer Receipt Received** | Cash / Bank | `:1010-000` Operating Bank Account | `:1200-000` Accounts Receivable | `CustomerReceipt` |
| **Customer Security Deposit Received** | Customer Liability | `:1010-000` Operating Bank Account | `:2300-000` Customer Rental Deposits | `DepositReceipt` |
| **Owner Settlement AP Bill** | Owner AP Expense | `:5100-000` Vehicle Lease Expense<br/>`:5110-000` Owner Usage Expense | `:2100-000` Accounts Payable (Lessor) | `OwnerRentalSettlement` |
| **Owner Statutory 10% WHT** | Statutory Liability | `:2100-000` Accounts Payable (Lessor) | `:3305-000` Withholding Tax Payable | `OwnerRentalSettlement` |
| **Fleet Maintenance Chargeback** | AP Deduction | `:2100-000` Accounts Payable (Lessor) | `:5200-000` Fleet Maintenance Recovery | `OwnerRentalSettlement` |
| **Owner Disbursement Paid** | AP Settlement | `:2100-000` Accounts Payable (Lessor) | `:1010-000` Operating Bank Account | `SupplierDisbursement` |

---

## 8. Enterprise Reporting Suite

AutoERP includes **8 enterprise analytical reports** plus dedicated **Telematics Statements**. Every report supports interactive web filtering, **PDF Download**, **Print View**, and **HTML Export**.

### 8.1 Telematics Statement of Account (HTML, Print, PDF)

Accessible at `GET /api/v1/vehicle-rental/billing/documents/{id}/statement`:
- Rendered via [`billing-cycle-statement.blade.php`](file:///d:/project-new/AutoERP/resources/views/reports/vehicle-rental/billing-cycle-statement.blade.php).
- Displays comprehensive operational telemetry:
  - Total Trips Count, Commercial Kilometers Run
  - Multi-tier Overtime Breakdown: Normal OT (1.5x), Double OT (2.0x), Triple OT (3.0x)
  - Night-Out Counts, Expressway Toll Summaries
  - Assigned Physical Vehicles (Registration, Make, Model, Custody Days)
  - Assigned Chauffeurs (Name, Employee Number, Duty Shift Hours)
  - Complete Itemized Financial Breakdown (Gross Base, Usage, WHT, Deductions, Paid, Balance Due)

### 8.2 The 8 Core Analytical Reports

```
Reports Directory: app/Modules/VehicleRental/Services/
├── FleetUtilizationReportService.php          # Daily fleet operational utilization %
├── VehicleRevenueLicenseReportService.php     # Revenue license, insurance & fitness alerts
├── RentalRevenueReportService.php             # Periodic revenue breakdown by customer
├── LesseeOutstandingReportService.php         # AR aging (Current, 30, 60, 90+ days)
├── OwnerDisbursementReportService.php         # Detail I & II Lessor settlement statements
├── VehicleProfitabilityReportService.php      # Dual-sided vehicle P&L analysis
├── RentalIncidentReportService.php            # Damage, repair costs & insurance claims
└── RunningChartReportService.php              # 31-day physical trip log matrix
```

1. **Fleet Utilization Report:** Computes total active fleet days, days under customer lease, days in workshop maintenance, and fleet utilization rate ($$\frac{\text{Rented Days}}{\text{Total Available Days}} \times 100$$).
2. **Revenue Licenses & Regulatory Compliance Report:** Monitors expiry dates for road revenue licenses, comprehensive insurance policies, fitness certificates, and emission test certificates. Displays alert status badges (`Valid`, `Expiring Soon (<= 30 days)`, `Expired`).
3. **Rental Revenue Report:** Detailed periodic revenue ledger itemizing base rent, excess km, overtime, tolls, gross revenue, taxes, and net earnings grouped by customer account.
4. **Lessee Outstanding Report:** Comprehensive Accounts Receivable aging schedule for corporate rental customers, showing total invoiced, total payments received, credits allocated, unapplied advances, aging buckets, and net collectible balances.
5. **Owner Disbursement Report (Detail I & II):** Statement of earnings and disbursements for vehicle owners. Detail I summarizes gross hire, overtime earnings, and net payable. Detail II lists discrete deductions, maintenance recoveries, WHT amounts, and disbursement cheque numbers.
6. **Vehicle Profitability Report:** Dual-sided financial performance analysis per physical vehicle registration:
   $$\text{Revenue} = \text{Customer Base Rent} + \text{Customer Usage Charges} + \text{Expense Recoveries}$$
   $$\text{Direct Cost} = \text{Owner Lease Rent} + \text{Owner Usage} + \text{Fleet Maintenance} + \text{Bank Finance Interest}$$
   $$\text{Net Profit} = \text{Revenue} - \text{Direct Cost} \quad \text{and} \quad \text{Profit Margin \%}$$
7. **Damage Incidents Summary Report:** Overview of accidents, damage claims, third-party liability, estimated vs actual repair costs, and insurance settlement statuses.
8. **Running Chart Sheet Report:** 31-day operational trip sheet matrix for a specific vehicle and month, reproducing the official regulatory transport logbook.

---

## 9. Frontend Workspaces & UI/UX Standards

The frontend is built with React 19, TypeScript, and Tailwind CSS under [`resources/js/modules/vehicle-rental/`](file:///d:/project-new/AutoERP/resources/js/modules/vehicle-rental/).

### 9.1 Frontend Architecture & Component Hierarchy

```
App Routing
  ├── /vehicle-rental/dashboard          -> RentalDashboardPage.tsx
  ├── /vehicle-rental/reservations       -> ReservationsPage.tsx (ReservationEditor.tsx)
  ├── /vehicle-rental/customer/agreements-> AgreementsPage.tsx
  ├── /vehicle-rental/owner/agreements   -> AgreementsPage.tsx
  │     ├── AgreementEditor.tsx
  │     ├── AgreementTermsGrid.tsx
  │     ├── VehicleUsePanel.tsx (VehicleUseEditor.tsx, ReassignDriverModal.tsx)
  │     ├── AgreementFinancialSummaryPanel.tsx
  │     ├── AgreementPaymentModal.tsx
  │     └── DepositPanel.tsx
  ├── /vehicle-rental/vehicle-uses       -> VehicleUseRegisterPage.tsx
  ├── /vehicle-rental/running-charts     -> RunningChartRegisterPage.tsx (RunningChartEditor.tsx)
  ├── /vehicle-rental/billing            -> RentalInvoicesAndBillsPage.tsx
  │     ├── BillingCycleWizardModal.tsx
  │     ├── OwnerSettlementModal.tsx
  │     └── RentalBillingDocumentDetailModal.tsx
  ├── /vehicle-rental/payments           -> RentalPaymentsPage.tsx
  ├── /vehicle-rental/expenses           -> RentalExpensesPage.tsx (RentalExpenseEditorModal.tsx)
  ├── /vehicle-rental/incidents          -> IncidentRegisterPage.tsx (IncidentAttachmentsPanel.tsx)
  ├── /vehicle-rental/finance            -> VehicleFinancePage.tsx (VehicleFinanceEditor.tsx)
  └── /vehicle-rental/reports/*          -> RentalReportsPage.tsx (7 sub-report pages)
```

### 9.2 Dedicated Invoices and Owner Bills Workspace

- **Page Component:** [`RentalInvoicesAndBillsPage.tsx`](file:///d:/project-new/AutoERP/resources/js/modules/vehicle-rental/RentalInvoicesAndBillsPage.tsx)
- **Route:** `/vehicle-rental/billing`
- **Features:**
  - **Dual Operational Tabs:** AR tab (Customer Invoices, Outbound) and AP tab (Owner Bills, Inbound), dynamically gated by user permissions.
  - **Financial KPI Cards:** Real-time Gross Invoiced, Total Paid, and Balance Due metric badges.
  - **Comprehensive Filters:** Search by reference, party, or vehicle registration; filter by document status (`draft`, `approved`, `posted`, `partially_paid`, `paid`, `cancelled`) or balance status (`unpaid`, `partially_paid`, `paid`, `overdue`).
  - **Action Hub:** Launch Billing Cycle Wizard, Direct Base Rent Invoice, or Generate Owner Settlement Bill.
  - **Detail Modal:** [`RentalBillingDocumentDetailModal.tsx`](file:///d:/project-new/AutoERP/resources/js/modules/vehicle-rental/RentalBillingDocumentDetailModal.tsx) opens an operational summary strip (KM, OT, Nights), itemized charges with discount columns, financial totals card, and action buttons for Print, PDF, Telematics Statement, and Document Cancellation.

### 9.3 Agreements Management Workspace

- **Page Component:** [`AgreementsPage.tsx`](file:///d:/project-new/AutoERP/resources/js/modules/vehicle-rental/AgreementsPage.tsx)
- **Routes:** `/vehicle-rental/customer/agreements` and `/vehicle-rental/owner/agreements`
- **Sub-Panels:**
  - `AgreementEditor.tsx`: Term builder with strict decimal regex validations.
  - `AgreementFinancialSummaryPanel.tsx`: Live financial status displaying invoiced totals, payments received, credit notes, and net balance due.
  - `AgreementPaymentModal.tsx`: Tender payments directly with cross-agreement settlement toggles.
  - `AgreementSuccessorForm.tsx`: Create versioned successor contracts.
  - `VehicleUsePanel.tsx`: View assigned vehicles, trigger handovers, returns, and replacements.

### 9.4 Vehicle Custody & Running Chart Registers

- **Vehicle Use Register (`VehicleUseRegisterPage.tsx`):** View all planned, in-custody, and returned vehicles across the company fleet. Filter by customer, vehicle, or date range. Trigger handovers, returns, or mid-lease replacements.
- **Running Chart Register (`RunningChartRegisterPage.tsx`):** Search daily trip sheets by reference, driver, or notes. Review odometer readings, overtime minutes, and AC modes. Trigger finalization or reversal.

### 9.5 UI/UX Invariants & Design Principles

1. **Never Expose Raw Foreign Keys:**
   - Database IDs (`party_id`, `vehicle_id`, `driver_employee_id`) are never displayed to users.
   - All relations are presented as human-readable badges (e.g. `CAS-4912 · Toyota HiAce Super GL`, `John Silva · EMP-0104`).
2. **Controlled Selection Components:**
   - Dropdown autocomplete selectors with search prevent blind foreign key input.
3. **Speed and Clarity Over Information Overload:**
   - Primary action modals display only required operational fields. Advanced analytics, history audits, and document logs are housed in secondary tabs or expandable drawers.
4. **Explicit Feedback & Safety Confirmation:**
   - Irreversible actions (cancelling an invoice, reversing a payment, returning a vehicle) require explicit confirmation dialogs explaining the downstream accounting and custody impact.

### 9.6 End-to-End User Journeys

#### User Journey 1: Fleet Dispatcher
1. Opens `/vehicle-rental/customer/agreements` and navigates to the active agreement.
2. Clicks "Assign Vehicle", selects vehicle `CAS-4912`, driver `EMP-0104`, and planned custody dates.
3. On vehicle handover, clicks "Handover", records actual start time and odometer reading (e.g. 45,200 km). Vehicle status updates to `Rented`.
4. Daily: Opens `/vehicle-rental/running-charts`, clicks "New Running Chart", enters trip odometers, overtime minutes, AC mode, and highway toll amounts. Clicks "Finalize Chart".
5. On lease completion, clicks "Return Vehicle", enters return odometer (e.g. 48,620 km). Vehicle status returns to `Active`.

#### User Journey 2: Billing Specialist
1. Opens `/vehicle-rental/billing` on the 1st of the month.
2. Clicks "New Billing Cycle", selects customer agreement, sets period (e.g. Oct 1 to Oct 31), checks "Issue Stage 1 Initial Invoice", and confirms. Stage 1 Base Rent invoice is issued.
3. At month end, clicks "Attach Running Charts". System detects 31 finalized trip sheets, tallies excess km and overtime, and advances cycle to `Stage2Attached`.
4. Clicks "Finalize Cycle" in Mode A. System generates the Supplementary Usage Invoice.
5. Clicks "View Statement" to generate the printable Telematics Statement with PDF export for the client.

#### User Journey 3: Financial Accountant
1. Opens `/vehicle-rental/billing` and switches to the "Owner Bills (AP)" tab.
2. Clicks "Generate Owner Settlement Bill", selects owner agreement and month. System calculates gross lease rent, overtime reimbursement, 10% WHT, and maintenance chargebacks within capacity.
3. Clicks "Save Bill". An AP Purchase Invoice is created in `Modules\Invoice`.
4. When the customer tenders a lump sum for multiple agreements, navigates to `/vehicle-rental/payments`, opens the Party Settlement Grid, allocates payments across open invoices, and records the cash/bank tender.

---

## 10. Complete REST API Specification (65+ Endpoints)

All routes are prefixed with `/api/v1/vehicle-rental` and require authentication and the `vehicle_rental` tenant feature.

| Method | Endpoint | Permission Required | Purpose |
| :--- | :--- | :--- | :--- |
| `GET` | `/dashboard` | `vehicle_rental.dashboard.view` | Rental KPI summary and alerts |
| `GET` | `/driver-employees` | `vehicle_rental.use.view` | Directory of available company chauffeurs |
| **Reservations Group** | | | |
| `GET` | `/reservations` | `vehicle_rental.reservation.view` | List booking inquiries with filters |
| `POST` | `/reservations` | `vehicle_rental.reservation.manage` | Create new rental reservation |
| `GET` | `/reservations/{id}` | `vehicle_rental.reservation.view` | View reservation detail |
| `PUT` | `/reservations/{id}` | `vehicle_rental.reservation.manage` | Update reservation details |
| `DELETE`| `/reservations/{id}` | `vehicle_rental.reservation.manage` | Delete draft reservation |
| `PATCH`| `/reservations/{id}/transition` | `vehicle_rental.reservation.manage` | Advance reservation status |
| `POST` | `/reservations/{id}/convert` | `vehicle_rental.reservation.manage` | Convert reservation to customer agreement |
| **Finance Agreements Group** | | | |
| `GET` | `/finance-agreements` | `vehicle_rental.finance.view` | List vehicle finance contracts |
| `POST` | `/finance-agreements` | `vehicle_rental.finance.manage` | Create hire-purchase finance agreement |
| `GET` | `/finance-agreements/{id}` | `vehicle_rental.finance.view` | View finance contract & installments |
| `DELETE`| `/finance-agreements/{id}` | `vehicle_rental.finance.manage` | Delete draft finance agreement |
| `PATCH`| `/finance-agreements/{id}/activate` | `vehicle_rental.finance.manage` | Activate finance contract |
| `PATCH`| `/finance-agreements/{id}/cancel` | `vehicle_rental.finance.manage` | Cancel finance contract |
| `POST` | `/finance-agreements/installments/{id}/payable` | `vehicle_rental.finance.manage` | Create AP bill voucher for installment |
| `PATCH`| `/finance-agreements/installments/refresh-due-statuses` | `vehicle_rental.finance.manage` | Refresh due status of installments |
| **Fleet Expenses Group** | | | |
| `GET` | `/expenses` | `vehicle_rental.expense.view` | List fleet expenses |
| `POST` | `/expenses` | `vehicle_rental.expense.manage` | Create operational fleet expense |
| `GET` | `/expenses/{id}` | `vehicle_rental.expense.view` | View expense details & allocations |
| `DELETE`| `/expenses/{id}` | `vehicle_rental.expense.manage` | Delete draft expense |
| `PATCH`| `/expenses/{id}/transition` | `vehicle_rental.expense.manage` | Advance expense status |
| `POST` | `/expenses/allocations/{id}/bill` | `customer.bill` | Bill customer recovery allocation |
| `POST` | `/expenses/allocations/{id}/unlink-invoice` | `customer.bill` | Unlink allocation from cancelled bill |
| **Damage Incidents Group** | | | |
| `GET` | `/incidents` | `vehicle_rental.incident.view` | Master register of damage incidents |
| `POST` | `/incidents` | `vehicle_rental.incident.record` | Record damage incident |
| `GET` | `/incidents/{id}` | `vehicle_rental.incident.view` | View incident details |
| `DELETE`| `/incidents/{id}` | `vehicle_rental.incident.record` | Delete draft incident |
| `GET` | `/incidents/{id}/history` | `vehicle_rental.incident.view` | View incident audit history |
| `POST` | `/incidents/{id}/attachments` | `vehicle_rental.incident.record` | Upload private file proof attachment |
| `GET` | `/incidents/{id}/attachments/{att}` | `vehicle_rental.incident.view` | Download incident attachment proof |
| `GET` | `/incidents/vehicle-use-options` | `vehicle_rental.incident.view` | Query candidate custody uses |
| `GET` | `/incidents/vehicle-uses/{use}/running-chart-options` | `vehicle_rental.incident.view` | Query candidate running charts |
| `POST` | `/incidents/{id}/{action}` | `vehicle_rental.incident.review` | Review incident (`confirm`, `reject`) |
| **Agreements Group ({kind} = customer \| owner)** | | | |
| `GET` | `/{kind}/agreements` | `customer.view` / `owner.view` | List customer or owner agreements |
| `POST` | `/{kind}/agreements` | `customer.manage` / `owner.manage` | Create commercial agreement |
| `GET` | `/{kind}/agreements/{id}` | `customer.view` / `owner.view` | View agreement details & terms |
| `PUT` | `/{kind}/agreements/{id}` | `customer.manage` / `owner.manage` | Update draft agreement terms |
| `DELETE`| `/{kind}/agreements/{id}` | `customer.manage` / `owner.manage` | Delete draft agreement |
| `POST` | `/{kind}/agreements/{id}/successor` | `customer.manage` / `owner.manage` | Create successor contract lineage |
| `GET` | `/{kind}/agreements/{id}/history` | `customer.view` / `owner.view` | View agreement revision history |
| `GET` | `/{kind}/agreements/{id}/financial-summary`| `invoice.view` | Invoiced, paid, and balance metrics |
| `POST` | `/{kind}/agreements/{id}/financial-adjustments` | `customer.manage` | Create agreement debit/credit note |
| `POST` | `/{kind}/agreements/{id}/base-rent-preview` | `customer.bill` | Preview base rent calculation |
| `POST` | `/{kind}/agreements/{id}/{action}` | `customer.manage` / `owner.manage` | Transition agreement (`activate`, `close`) |
| `GET` | `/{kind}/agreements/{id}/base-charges` | `customer.view` / `owner.view` | Direct base rent charge schedule |
| `POST` | `/{kind}/agreements/{id}/base-charges` | `customer.bill` / `owner.bill` | Create direct base charge |
| `POST` | `/{kind}/agreements/{id}/base-charges/{c}/void` | `customer.bill` | Void direct base charge |
| `POST` | `/{kind}/agreements/{id}/base-charges/{c}/reissue`| `customer.bill` | Reissue direct base charge |
| **Payments & Deposits Group** | | | |
| `GET` | `/{kind}/agreements/{id}/payments` | `payments.view` | List payments for agreement |
| `POST` | `/{kind}/agreements/{id}/payments` | `payments.create` | Tender payment on single agreement |
| `POST` | `/{kind}/agreements/{id}/payments/{p}/allocate` | `payments.create` | Allocate payment to open invoice |
| `POST` | `/{kind}/agreements/{id}/payments/{p}/reverse` | `payments.create` | Reverse payment |
| `POST` | `/{kind}/agreements/{id}/payments/{p}/refund` | `payments.create` | Refund unapplied payment balance |
| `GET` | `/{kind}/parties/{partyId}/open-documents` | `payments.view` | Open invoices across ALL agreements |
| `POST` | `/{kind}/parties/{partyId}/payments` | `payments.create` | Multi-agreement payment tender |
| `POST` | `/{kind}/parties/{partyId}/payments/{p}/allocate` | `payments.create` | Party-wide advance allocation |
| `GET` | `/customer/agreements/{id}/deposits` | `payments.view` | List customer security deposits |
| `POST` | `/customer/agreements/{id}/deposits` | `payments.create` | Record customer deposit receipt |
| `POST` | `/customer/agreements/{id}/deposits/allocate` | `payments.create` | Allocate deposit against invoice |
| **Vehicle Custody Group** | | | |
| `GET` | `/vehicle-uses` | `vehicle_rental.use.view` | Master register of vehicle custody uses |
| `GET` | `/customer/agreements/{id}/vehicles` | `vehicle_rental.use.view` | Custody uses for specific agreement |
| `POST` | `/customer/agreements/{id}/vehicles` | `vehicle_rental.use.manage` | Plan vehicle assignment |
| `GET` | `/vehicles/{vehicle}/sources` | `vehicle_rental.use.view` | Query sourcing owner agreements |
| `GET` | `/vehicle-uses/{id}` | `vehicle_rental.use.view` | View custody use record |
| `DELETE`| `/vehicle-uses/{id}` | `vehicle_rental.use.manage` | Cancel planned assignment |
| `POST` | `/vehicle-uses/{id}/replace` | `vehicle_rental.use.manage` | Mid-lease atomic vehicle swap |
| `POST` | `/vehicle-uses/{id}/replace-vehicle` | `vehicle_rental.use.manage` | Alternative vehicle replacement route |
| `POST` | `/vehicle-uses/{id}/reassign-driver` | `vehicle_rental.use.manage` | Change chauffeur on custody use |
| `GET` | `/vehicle-uses/{id}/history` | `vehicle_rental.use.view` | Custody handover/return history |
| `POST` | `/vehicle-uses/{id}/{action}` | `vehicle_rental.use.manage` | Transition use (`handover`, `return`, `cancel`) |
| `GET` | `/vehicles/available` | `vehicle_rental.use.view` | Query unblocked vehicles for dates |
| **Daily Running Charts Group** | | | |
| `GET` | `/running-charts` | `vehicle_rental.chart.view` | Master register of daily trip sheets |
| `GET` | `/vehicle-uses/{use}/running-charts` | `vehicle_rental.chart.view` | Trip sheets for specific custody use |
| `POST` | `/vehicle-uses/{use}/running-charts` | `vehicle_rental.chart.manage` | Create daily running chart |
| `GET` | `/running-charts/{id}` | `vehicle_rental.chart.view` | View running chart detail |
| `PUT` | `/running-charts/{id}` | `vehicle_rental.chart.manage` | Update draft running chart |
| `DELETE`| `/running-charts/{id}` | `vehicle_rental.chart.manage` | Delete draft running chart |
| `GET` | `/running-charts/{id}/history` | `vehicle_rental.chart.view` | View running chart audit history |
| `POST` | `/running-charts/{id}/{action}` | `chart.finalize` / `chart.reverse` | Finalize or reverse running chart |
| `POST` | `/running-charts/{id}/reassign-driver` | `vehicle_rental.chart.manage` | Reassign chauffeur on running chart |
| `GET` | `/running-charts/{id}/{kind}/charges` | `invoice.view` | Direct usage charges on running chart |
| `GET` | `/running-charts/{id}/{kind}/charges/mileage`| `invoice.view` | Preview direct mileage calculation |
| `POST` | `/running-charts/{id}/{kind}/charges/mileage`| `customer.bill` | Assess direct mileage charge |
| `POST` | `/running-charts/{id}/{kind}/charges` | `customer.bill` / `owner.bill` | Create direct usage charge |
| `POST` | `/running-charts/{id}/{kind}/charges/{c}/void` | `customer.bill` | Void direct usage charge |
| `POST` | `/running-charts/{id}/{kind}/charges/{c}/reissue`| `customer.bill` | Reissue direct usage charge |
| **Two-Stage Billing Cycles Group** | | | |
| `GET` | `/billing-cycles` | `customer.bill` / `owner.bill` | List periodic billing cycles |
| `POST` | `/{kind}/agreements/{id}/billing-cycles` | `customer.bill` / `owner.bill` | Create Two-Stage billing cycle |
| `GET` | `/billing-cycles/{id}` | `customer.bill` / `owner.bill` | View billing cycle details |
| `GET` | `/billing-cycles/{id}/eligible-charts` | `customer.bill` / `owner.bill` | Query candidate running charts |
| `POST` | `/billing-cycles/{id}/charts` | `customer.bill` / `owner.bill` | Attach running charts to cycle |
| `POST` | `/billing-cycles/{id}/items` | `customer.bill` / `owner.bill` | Add ad-hoc addition or deduction item |
| `DELETE`| `/billing-cycles/{id}/items/{itemId}` | `customer.bill` / `owner.bill` | Remove ad-hoc addition/deduction item |
| `GET` | `/billing-cycles/{id}/preview` | `customer.bill` / `owner.bill` | Preview final stage & vehicle attributions|
| `POST` | `/billing-cycles/{id}/finalize` | `customer.bill` / `owner.bill` | Finalize cycle & issue Mode A/B invoice |
| `GET` | `/billing-cycles/{id}/export/{format}` | `customer.bill` | Export cycle summary (PDF, print, HTML) |
| **Adjustments & Reassignments Registers** | | | |
| `GET` | `/adjustments` | `customer.view` / `owner.view` | List agreement financial adjustments |
| `POST` | `/adjustments` | `customer.manage` / `owner.manage` | Create agreement financial adjustment |
| `GET` | `/adjustments/{id}` | `customer.view` / `owner.view` | View adjustment detail |
| `GET` | `/reassignments` | `vehicle_rental.use.view` | Register of driver & vehicle swaps |
| **Dedicated Rental Invoices and Owner Bills Workspace** | | | |
| `GET` | `/billing/documents` | `invoice.view` | Rental invoices & bills register |
| `GET` | `/billing/summary` | `invoice.view` | Dedicated billing KPI summary metrics |
| `GET` | `/billing/documents/{id}` | `invoice.view` | Detailed document with telematics breakdown|
| `GET` | `/billing/documents/{id}/statement` | `invoice.view` | Telematics statement (HTML, Print, PDF) |
| `PUT` | `/billing/documents/{id}` | `customer.bill` / `owner.bill` | Update due date or notes on document |
| `POST` | `/billing/documents/{id}/cancel` | `invoice.void` | Cancel invoice & restore billing cycles |
| `POST` | `/owner-settlement-bill` | `vehicle_rental.owner.bill` | Generate itemized owner settlement AP bill|
| **Analytical Reports & Telematics Exports** | | | |
| `GET` | `/reports/running-chart-sheet` | `vehicle_rental.dashboard.view` | 31-day running chart sheet log report |
| `GET` | `/reports/running-chart-sheet/export/{f}`| `vehicle_rental.dashboard.view` | Export running chart sheet (PDF, print, HTML)|
| `GET` | `/reports/fleet-utilization` | `vehicle_rental.dashboard.view` | Fleet utilization % report |
| `GET` | `/reports/fleet-utilization/export/{f}` | `vehicle_rental.dashboard.view` | Export fleet utilization report |
| `GET` | `/reports/revenue-licenses` | `vehicle_rental.dashboard.view` | Compliance & license expiry report |
| `GET` | `/reports/revenue-licenses/export/{f}` | `vehicle_rental.dashboard.view` | Export revenue license report |
| `GET` | `/reports/rental-revenue` | `vehicle_rental.dashboard.view` | Customer rental revenue report |
| `GET` | `/reports/rental-revenue/export/{f}` | `vehicle_rental.dashboard.view` | Export rental revenue report |
| `GET` | `/reports/lessee-outstanding` | `vehicle_rental.dashboard.view` | AR aging & outstanding balance report |
| `GET` | `/reports/lessee-outstanding/export/{f}`| `vehicle_rental.dashboard.view` | Export lessee outstanding report |
| `GET` | `/reports/owner-disbursements` | `vehicle_rental.dashboard.view` | Owner settlement disbursement report |
| `GET` | `/reports/owner-disbursements/export/{f}`| `vehicle_rental.dashboard.view`| Export owner disbursement report |
| `GET` | `/reports/vehicle-profitability` | `vehicle_rental.dashboard.view` | Dual-sided vehicle P&L report |
| `GET` | `/reports/vehicle-profitability/export/{f}`| `vehicle_rental.dashboard.view`| Export vehicle profitability report |
| `GET` | `/reports/incidents-summary` | `vehicle_rental.dashboard.view` | Incidents & damage claims summary |
| `GET` | `/reports/incidents-summary/export/{f}`| `vehicle_rental.dashboard.view` | Export incidents summary report |

---

## 11. Developer Verification, Testing & Extension Guide

### 11.1 Running the Verification Suites

Before deploying changes to `VehicleRental`, developers must run the test suites to ensure zero regressions:

```bash
# 1. Run all VehicleRental backend feature and unit tests (306 tests, 2,477 assertions)
php artisan test app/Modules/VehicleRental/Tests/

# 2. Run Invoice module tests (42 tests)
php artisan test app/Modules/Invoice/Tests/

# 3. Run Payment module tests (51 tests)
php artisan test app/Modules/Payment/Tests/

# 4. Run Architectural integrity suite (12 tests)
php artisan test tests/Unit/Architecture/

# 5. Run Frontend Vitest suites for vehicle rental (33 test suites, 154 tests)
npx vitest run resources/js/modules/vehicle-rental/

# 6. Run TypeScript compiler strict typecheck (must pass with 0 errors)
npm run typecheck
```

### 11.2 Guidelines for Extending the Module

When extending or maintaining `VehicleRental`, developers must follow these engineering rules:

1. **Never Bypass Optimistic Concurrency:**
   - Any service method that updates an agreement, custody use, running chart, billing cycle, or expense **must** validate `expected_version` and increment `row_version += 1`.
2. **Never Use Floating Point Numbers:**
   - Use `DecimalMath` methods (`$this->math->add()`, `$this->math->sub()`, `$this->math->mul()`, `$this->math->div()`) with scale 6.
3. **Respect Module Ownership:**
   - `VehicleRental` creates semantic commercial facts and passes them to `Modules\Invoice` and `Modules\Payment`. Do not write raw SQL inserts into `invoices` or `payments` tables.
4. **Preserve Bidirectional Restoration:**
   - If introducing a new billing item or charge allocation, ensure [`RentalBillingDocumentService::restoreSourcesOnCancellation()`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/RentalBillingDocumentService.php) properly restores its source status upon invoice cancellation.
5. **Always Update Shared Memory:**
   - After completing any modification, record an append-only change document in [`docs/changes/`](file:///d:/project-new/AutoERP/docs/changes) describing what was changed, why, and the verification evidence. Never edit or delete historical change records.

---

*This guide represents the authoritative, complete specification of the AutoERP Vehicle Rental module. For inquiries or updates, refer to recent change records in `docs/changes/`.*
