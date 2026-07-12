# AutoERP Complete End-to-End Deep Audit

**Audit date:** 2026-07-12  
**Repository:** `kasunvimarshana/AutoERP`  
**Authoritative branch:** `worktree-0.0.8`  
**Audited head:** `314950e6f20b00eb9b22e91470b6c64631ffd495`  
**Primary focus:** Complete project architecture, module ownership, Finance source ownership, posting profiles, accounting lifecycle, tests, documents, security boundaries, migrations, frontend access policies, and production-readiness claims.

---

## 1. Executive verdict

AutoERP has a strong technical foundation in several areas:

- tenant and organization-unit isolation;
- exact decimal arithmetic;
- controlled authentication and authorization boundaries;
- inventory quantity, reservation, serial, and valuation integrity;
- immutable Finance journals and reversal entries;
- Invoice source allocation and balance tracking;
- Payment allocation, refund, cheque, and reversal lifecycles;
- Vehicle Rental agreement, allocation, running-chart, calculation, deposit, and concurrency controls;
- SQLite and MySQL/MariaDB automated test parity.

However, **the complete project is not production-ready for financial operations**.

The primary reason is not failing tests. The primary reason is an incomplete and inconsistent **business source → financial document → Tax → Finance journal → reversal** ownership chain.

The most serious confirmed findings are:

1. An Invoice can reach `Posted` without a Finance journal.
2. A posted Invoice has no coordinated business-document reversal lifecycle.
3. Payment posting selects accounts mainly by direction, not by payment type and party semantics.
4. Generic Purchase, GRN, Inventory, Vehicle Service, Vehicle Rental, and Vehicle Finance paths do not uniformly post Finance journals.
5. Tax transactions can be marked posted independently from their GL tax entries.
6. There is no accounting-period close, soft-close, or reopen control.
7. Persistent-database upgrade strategy is inconsistent with the fresh-baseline migration policy.
8. Production health checks can report ready without proving worker, scheduler, SMTP, cache, storage, backup, TLS, or alerting health.

**Recommended classification:**

| Classification | Status |
|---|---|
| Source-code release candidate | Conditional pass |
| Local automated tests | Pass |
| SQLite/MySQL parity | Pass |
| Staging deployment | Ready after migration rehearsal |
| Financial UAT | Not ready until posting ownership is corrected |
| Production go-live | Blocked |

---

## 2. Audit method and limitations

### Audited evidence

The audit reviewed:

- all registered application modules and service providers;
- module API route definitions and permissions;
- high-risk owner services and cross-module integrations;
- Finance posting contracts, profiles, rules, roles, account assignments, journal creation, posting, ledger, and reversal;
- Invoice, Payment, Purchase, Inventory, Tax, Vehicle Service, Vehicle Rental, and Vehicle Finance financial workflows;
- database migrations and upgrade-migration policy;
- PHPUnit SQLite and MySQL configurations;
- frontend route entitlement composition;
- production health/readiness logic;
- reporting definitions;
- test intent and test implementation style;
- historical change/readiness documentation;
- latest user-provided automated verification output.

### Verification already completed by the user

At the audited head:

- targeted deposit receipt identity suite: **4 passed / 7 assertions**;
- full Laravel SQLite suite: **659 passed / 8,270 assertions**;
- full MySQL suite: **659 passed / 8,270 assertions**;
- previous frontend verification passed TypeScript, ESLint, Vite build, and **254 Vitest tests**.

### Limitations

This is a comprehensive source, architecture, tests, and documentation audit of the accessible authoritative branch. It is not proof that no undiscovered defect exists.

The following were **not executed in this audit environment**:

- live browser end-to-end testing;
- real parallel/concurrency load;
- real queue workers and scheduler;
- SMTP delivery;
- Redis/cache connectivity;
- production object storage;
- backup and restore;
- TLS and reverse-proxy configuration;
- dependency vulnerability scans;
- production migration rehearsal;
- penetration testing.

No code was modified during this audit.

---

## 3. Severity summary

| Severity | Meaning | Count |
|---|---|---:|
| P0 / Critical | Financial integrity or production release blocker | 5 |
| P1 / High | Major correctness, ownership, or operational risk | 13 |
| P2 / Medium | Maintainability, UX, traceability, incomplete control, or scoped decision | 11 |

Evidence status across the 29 registered findings: **25 confirmed**, **3 business decisions required**, and **1 operational/security control not verified**.

---

# 4. Critical release blockers

## P0-FIN-001 — Invoice can be `Posted` without a Finance journal

### Confirmed behavior

The Invoice lifecycle marks the Invoice as posted and posts Tax transactions. It does not invoke the Finance posting contract.

This affects:

- public/manual Invoice posting;
- generic Purchase supplier invoices;
- Vehicle Service invoices;
- Vehicle Rental lessee invoices;
- Vehicle Rental owner payables represented as inbound invoices;
- Vehicle Finance installment payables;
- any integration that asks `InvoiceCreationService` to create a posted Invoice.

### Impact

A document can be:

- visible as posted;
- settlement eligible;
- included in AR/AP aging;
- included in Invoice reports;
- carrying posted Tax transactions;

while no balanced Finance journal exists.

This can produce:

- AR/AP control accounts not matching Invoice balances;
- revenue and expense understatement;
- Tax reports not matching GL;
- incorrect trial balance, P&L, balance sheet, and profitability reports;
- payment journals clearing control accounts that were never established by the Invoice.

### Required correction

Invoice posting must become an atomic governed command:

```text
Lock Invoice and source facts
→ Validate accounting period
→ Build source-owned semantic posting plan
→ Post Tax and Finance exactly once
→ Store journal reference on Invoice
→ Mark Invoice Posted
→ Commit one transaction
```

A posted status must never be reachable without a successful journal unless the Invoice type is explicitly configured as non-accounting.

---

## P0-FIN-002 — No complete posted-Invoice reversal lifecycle

### Confirmed behavior

Posted Invoices cannot transition to Cancelled or Void. There is no public Invoice reversal route or Invoice-owned reversal coordinator.

The Finance module can reverse a journal manually, but doing so does not automatically:

- reverse the Invoice status;
- restore source quantities;
- reverse Tax transactions;
- reverse Invoice balance effects;
- restore Vehicle Rental calculation consumption;
- restore Purchase/GRN source availability;
- update Vehicle Service job status.

### Impact

The system correctly prevents destructive editing but does not provide the required correction mechanism for posted financial documents.

Fast Purchase is especially exposed:

- it posts Finance journals during creation;
- its Invoice is created as posted;
- the later generic Invoice lifecycle has no coordinated reversal;
- a manual Finance reversal would leave source documents inconsistent.

A receive-only Fast Purchase can also post an inventory receipt journal; GRN reversal reverses Inventory and Tax but does not reverse the Finance journal.

### Required correction

Create an Invoice-owned `InvoiceReversalService` that coordinates:

```text
Invoice lock
→ Source-module reversal eligibility
→ Finance journal reversal
→ Tax reversal
→ Invoice settlement/balance validation
→ Source allocation restoration
→ Invoice status = Reversed
→ Source module status synchronization
→ Immutable audit event
```

The business source module must own source restoration rules; Invoice owns the financial-document lifecycle; Finance owns journal reversal.

---

## P0-FIN-003 — Payment accounting is selected by direction, not business semantics

### Confirmed behavior

Current Payment posting logic effectively maps:

```text
Inbound  → Dr Cash/Bank, Cr Accounts Receivable
Outbound → Dr Accounts Payable, Cr Cash/Bank
```

The Payment domain supports multiple semantic types:

- customer receipt;
- rental receipt;
- service receipt;
- supplier payment;
- advance;
- refund;
- manual payment.

Direction alone is insufficient.

### Incorrect examples

- Rental security deposit receipt (`Advance`, inbound) credits Accounts Receivable instead of a customer-deposit/customer-advance liability.
- Customer refund (outbound) debits Accounts Payable.
- Supplier refund (inbound) credits Accounts Receivable.
- Unallocated customer advance and an allocated customer receipt require different accounting treatment.
- Refunds should reverse or consume the original economic classification, not use a generic direction profile.

### Required correction

Introduce a Payment posting policy selected by:

```text
payment_type
+ direction
+ party_type
+ allocation state
+ original payment type for refunds
```

Example semantic profiles—not hardcoded accounts—should include:

- customer receipt;
- supplier payment;
- customer advance receipt;
- customer deposit receipt;
- customer refund;
- supplier refund;
- advance application;
- deposit forfeiture;
- manual cash/bank transfer where explicitly allowed.

Payment remains the owner of instruments, allocations, refunds, and posting invocation. Finance remains the owner of account resolution and journals.

---

## P0-FIN-004 — Accounting periods do not exist

### Confirmed gap

Finance posting and journal reversal validate dates and balanced entries, but there is no:

- fiscal period master;
- open/soft-closed/closed state;
- period lock;
- reopen command;
- reopen permission;
- posting-date check against period status;
- reversal-date policy linked to period status;
- Tax/report freeze integration.

### Impact

A user can post or reverse into an already reported historical period and silently change:

- trial balance;
- profit and loss;
- balance sheet;
- Tax liability;
- bank reconciliation;
- AR/AP;
- management reports.

### Required correction

Finance must own:

```text
Accounting Year
└── Accounting Period
      Open
      Soft Closed
      Closed
      Reopened
```

Every Finance posting and reversal must validate the period. Source modules must not implement separate period logic.

---

## P0-DB-001 — Persistent database upgrade lifecycle is unresolved

### Confirmed contradiction

The project enforces clean one-table-per-file baseline migrations and prohibits ordinary patch migrations in module migration folders. Deployment scripts use `migrate --force`.

Editing an already-ran baseline migration does not upgrade an existing persistent database.

Finance has an isolated upgrade-migration mechanism, but there is no consistent project-wide released-schema upgrade contract.

### Impact

A deployed database can be missing columns, keys, constraints, or data migrations expected by current source code while `migrate --force` reports nothing pending.

### Required decision and correction

If no persistent production data exists:

1. declare an explicit schema freeze/baseline release;
2. recreate staging and production from the frozen baseline;
3. never edit released baseline migrations afterward.

If persistent data exists:

1. introduce project-wide forward-only upgrade migrations;
2. version the released schema;
3. test previous-release schema → current schema;
4. run migration rehearsal against a restored production-sized copy;
5. verify row counts, foreign keys, balances, and source links.

---

# 5. Finance ownership and posting-profile audit

## 5.1 Correct ownership boundary

The intended clean boundary is:

### Business/source module owns

- commercial meaning;
- source document;
- source line identity;
- economic amounts;
- source lifecycle;
- semantic posting keys;
- reversal eligibility.

### Invoice/Payment owns

- financial-document status;
- balance and settlement lifecycle;
- immutable document snapshots;
- instrument/allocation lifecycle for Payments;
- invocation of Finance within the same transaction.

### Tax owns

- tax determination;
- immutable tax snapshots;
- tax transactions;
- tax reversal facts;
- semantic tax posting keys.

### Finance owns

- account roles;
- posting profiles;
- effective-dated rules;
- role-to-account assignments;
- period validation;
- balanced journal creation;
- ledger entries;
- source-journal idempotency;
- journal reversal;
- financial statements.

Business modules must never select accounts by code. Finance must not invent business amounts or business document meaning.

---

## 5.2 Posting-profile architecture strengths

Current implementation has several good foundations:

- semantic `profileKey` lines instead of business modules selecting account IDs;
- effective-dated posting profile rules;
- account roles separated from account assignments;
- organization-specific assignment with tenant fallback;
- overlap checks;
- active/postable account validation;
- source-key and posting-fingerprint idempotency;
- immutable posted journals;
- deterministic ledger balance reconstruction;
- exact decimal calculations.

These foundations should be retained.

---

## 5.3 Posting-profile configuration defects

### FIN-PROFILE-001 — Runtime fallback is not clearly visible in configuration APIs

Runtime profile resolution supports:

```text
organization-specific profile
→ tenant-level profile fallback
```

The configuration list/update paths use different scope behavior:

- profile listing is organization-specific;
- account-assignment listing includes fallback rows;
- ending an assignment expects exact current organization scope;
- resources do not expose enough effective-scope metadata.

An administrator may not see the profile actually used by runtime posting.

### FIN-PROFILE-002 — Missing optimistic concurrency

Posting profiles, rules, roles, and assignments have no consistent `row_version` workflow.

Two administrators can edit effective-dated rules concurrently and overwrite or conflict unpredictably.

### FIN-PROFILE-003 — Owner service can relocate an existing profile

`PostingProfileService::save()` force-fills tenant and organization fields on an existing model. Controllers scope the model, but the owning service should enforce that an existing profile cannot change tenant/organization identity.

### FIN-PROFILE-004 — Date validation is inconsistent

Posting profile rule dates require exact `YYYY-MM-DD`. Account assignment request dates use broader request validation and are parsed later.

All effective-dated Finance configuration should share one strict date value object/validator.

### FIN-PROFILE-005 — Duplicate obsolete configuration implementation

`FinanceConfigurationController` is the routed owner, but `FinanceController` still contains older posting-profile and lookup methods. The duplicate implementation expects an obsolete `rules.account` relation while current rules use roles.

This is dead code and a future regression risk. Remove it from the wrong owner.

---

## 5.4 Default posting-profile coverage gaps

Seeded profiles cover basic sales, purchases, payments, inventory, service, and returns, but do not cover the complete current domain.

Missing or insufficient semantic profiles include:

- customer advance liability;
- rental security deposit liability;
- customer refund;
- supplier refund;
- deposit application;
- deposit forfeiture;
- rental income components;
- rental owner/lessor expense components;
- vehicle finance principal, interest, fees, and Tax;
- foreign exchange realized gain/loss;
- foreign exchange unrealized gain/loss;
- bank fees and charges;
- payroll and statutory liabilities if payroll enters scope;
- workshop inventory issue/COGS variation where required.

Do not add hardcoded accounts. Add semantic profiles and require configuration completeness before the corresponding workflow can post.

---

## 5.5 Confirmed posting mapping defect in Fast Purchase

Fast Purchase has a strong atomic create-time coordinator:

- Purchase Order;
- GRN;
- Inventory;
- supplier Invoice;
- Finance postings;
- Payment;
- idempotency and audit;

are coordinated inside one outer transaction.

However, the supplier-invoice Finance posting maps the “Withholding payable” line to the ordinary `payable` profile key instead of `withholding_payable`.

The default purchase profile already defines a distinct withholding role.

### Impact

Withholding liability is mixed into normal supplier AP, causing:

- supplier statement/control-account mismatch;
- incorrect withholding payable reporting;
- difficult Tax reconciliation.

### Required correction

Use the semantic withholding role produced by Tax/Invoice facts, not ordinary AP.

---

# 6. Source-to-ledger workflow matrix

| Workflow | Operational owner | Finance creation | Coordinated reversal | Verdict |
|---|---|---:|---:|---|
| Manual Invoice | Invoice | Missing | Missing | Critical |
| Generic Invoice post | Invoice | Missing | Missing | Critical |
| Generic supplier Invoice | Purchase + Invoice | Missing | Missing | Critical |
| Fast Purchase supplier Invoice | Purchase coordinator | Present | Missing | High |
| Generic GRN | Purchase + Inventory | Missing | Missing | High |
| Fast Purchase GRN | Purchase coordinator | Present | Missing | High |
| Inventory receipt/issue/adjustment/transfer | Inventory | Missing generally | Missing generally | High |
| Customer receipt | Payment | Present | Present | Mapping risk |
| Supplier payment | Payment | Present | Present | Strong |
| Customer/rental deposit receipt | Vehicle Rental + Payment | Present | Present | Wrong account classification |
| Payment refund | Payment | Present | Present | Wrong account classification risk |
| Vehicle Service Invoice | Vehicle Service + Invoice | Missing | Missing | Critical |
| Vehicle Rental lessee Invoice | Vehicle Rental + Invoice | Missing | Missing | Critical |
| Vehicle Rental owner payable | Vehicle Rental + Invoice | Missing | Missing | Critical |
| Vehicle Finance installment payable | Vehicle Rental + Invoice | Missing | Missing | Critical |
| Tax snapshot/transaction | Tax | Finance context available but not atomically consumed | Tax reversal exists, GL reversal not coordinated | High |
| Manual Finance journal | Finance | Present | Present | Source spoofing/traceability risk |
| Payment reversal | Payment + Finance | Present | Present | Strong |

---

# 7. Module-by-module audit matrix

## Audit

**Strengths**

- append-only audit event design;
- scoped authorization;
- sensitive payload sanitation;
- immutable persistence tests.

**Gaps**

- operational retention, archival, export, monitoring, and alert delivery not verified;
- cross-module audit completeness is not proven by an invariant test.

**Status:** Green/Amber.

---

## Auth

**Strengths**

- tenant and platform authentication separation;
- opaque token handling;
- refresh-cookie tests;
- trust-boundary tests;
- session revocation on access changes.

**Gaps**

- production rate limiting, key rotation, proxy/TLS behavior, brute-force monitoring, and penetration testing not verified.

**Status:** Green/Amber.

---

## Configuration

**Strengths**

- global → tenant → organization precedence;
- typed value validation;
- sensitive configuration permissions.

**Gaps**

- production readiness does not verify all critical settings;
- Finance posting configuration uses a separate scope UX that is not fully aligned with general configuration precedence.

**Status:** Green/Amber.

---

## Core

**Strengths**

- exact base-10 decimal math;
- explicit result and data-record contracts;
- tenant execution context;
- consistent API errors;
- tenant-owned model foundation.

**Gaps**

- static architecture tests cannot prove runtime dependency resolution, transactions, or concurrency.

**Status:** Green.

---

## Customer

**Strengths**

- master data ownership;
- separate authoritative credit profile;
- status history;
- tenant and organization scope.

**Gaps**

- Invoice posting does not centrally enforce customer credit allowance, credit limit, overdue policy, or on-hold state;
- customer advance accounting is missing from Payment posting profiles.

**Status:** Amber.

---

## Finance

**Strengths**

- journal and ledger mechanics;
- exact balancing;
- semantic role resolution;
- effective-dated configuration;
- source posting fingerprint;
- immutable reversal;
- financial statements and reconciliation foundations.

**Critical gaps**

- incomplete source ownership integrations;
- no accounting period;
- profile coverage/mapping gaps;
- configuration concurrency and scope UX;
- manual source spoofing;
- duplicate controller implementation.

**Status:** Red.

---

## HR

**Strengths**

- employee master;
- relations, skills, certifications, licenses;
- effective employee rates;
- availability and status history;
- granular backend permissions.

**Gaps**

- no Payroll module/provider/routes;
- salary processing, deductions, statutory liabilities, payslips, payroll posting, leave, attendance, and payroll period close are outside the implemented scope;
- frontend legacy access policy still has broad `/hr/*` fallback.

**Status:** Amber; scope decision required.

---

## Inventory

**Strengths**

- base-UOM preservation;
- stock balances;
- reservation/allocation;
- serial and batch controls;
- FIFO/weighted-average/standard/manual valuation;
- transfers, counts, adjustments, and reversals;
- deterministic stock locks.

**Critical gap**

- inventory value changes do not uniformly create/reverse Finance journals.

**Status:** Operationally green, financially red.

---

## Idempotency

**Strengths**

- operation identity, payload hashes, completion records;
- Fast Purchase uses it correctly.

**Gaps**

- real parallel request behavior and stale-in-progress recovery under production load not verified across all commands.

**Status:** Green/Amber.

---

## Invoice

**Strengths**

- exact line calculations;
- source and source-line allocation;
- immutable party/currency/item/UOM snapshots;
- idempotent manual creation;
- balance and settlement ownership;
- protected server-owned request fields;
- draft-only editing/deletion.

**Critical gaps**

- posted status without Finance;
- no posted reversal;
- Tax and GL are not atomic;
- lower-level integration validation depends on every caller;
- model exposes ambiguous customer/supplier relationships against one party ID.

**Status:** Red.

---

## Item

**Strengths**

- immutable effective-dated price revisions;
- UOM, variants, bundles, categories, brands;
- scope-aware lookups;
- price source separation from item master.

**Gaps**

- no major confirmed release blocker;
- performance of large lookups/catalogues should be load-tested.

**Status:** Green.

---

## Organization Unit

**Strengths**

- scoped current organization resolution;
- hierarchy integrity;
- permission definitions;
- portable uniqueness design.

**Gaps**

- profile/config fallback behavior must be made explicit to administrators.

**Status:** Green/Amber.

---

## Payment

**Strengths**

- instrument snapshots;
- payment methods;
- lifecycle states;
- allocations and unapplied balances;
- refund workflow;
- cheque printing;
- Finance posting and Finance reversal;
- concurrency/version checks.

**Critical gap**

- direction-only accounting classification is incorrect for advances, deposits, and refunds.

**Status:** Red until posting policy is corrected.

---

## Private Object

**Strengths**

- private storage abstraction;
- path-traversal protection.

**Gaps not verified**

- MIME/content validation;
- malware scanning;
- encryption-at-rest requirements;
- signed URL expiry;
- object retention and legal hold;
- orphan cleanup;
- production object-store access and backup.

**Status:** Amber.

---

## Purchase

**Strengths**

- Purchase Orders, GRNs, supplier invoices, returns, debit notes;
- adjustment allocation;
- partial receipt/invoice/return;
- source overlap prevention;
- Fast Purchase idempotency;
- strong tenant/organization validation.

**Critical gaps**

- generic GRN and supplier-invoice paths do not uniformly post Finance;
- Fast Purchase Finance reversal is not coordinated with later source reversal;
- Fast Purchase withholding maps to ordinary AP;
- receive-only GRN reversal can leave the original Finance receipt journal active.

**Status:** Red for financial production.

---

## Reference Data

**Strengths**

- global/tenant lookup boundaries;
- permission catalogue;
- shared currencies and reference masters.

**Gaps**

- reference changes that affect historical business meaning must remain snapshot-based; this is mostly handled by financial document snapshots.

**Status:** Green.

---

## Reporting

**Strengths**

- reusable definitions;
- exact decimal summaries;
- HTML, Excel, and real PDF export;
- permission boundary;
- generic Invoice, Payment, Inventory, Purchase, and Finance reports.

**Gaps**

- reports are only as accurate as Finance source completeness;
- missing source-vs-GL exception report;
- Vehicle Rental financial/settlement reports incomplete;
- no production row-volume/performance baseline;
- no scheduled delivery or archival verification.

**Status:** Amber/Red until Finance is corrected.

---

## Sequence

**Strengths**

- tenant-scoped monotonic generation;
- conflict handling;
- typed concurrency errors.

**Gaps**

- high-concurrency production throughput not load-tested.

**Status:** Green/Amber.

---

## Supplier

**Strengths**

- contacts, addresses, bank accounts, categories, documents, item mappings;
- credit profile and status history;
- blacklisting;
- tenant/organization scope;
- opening balance correctly rejected from master ownership.

**Gaps**

- supplier refund/advance accounting policy is incomplete in Payment;
- AP policy depends on missing Invoice posting integration.

**Status:** Amber.

---

## Tax

**Strengths**

- effective Tax masters and groups;
- inclusive/exclusive/compound/withholding calculations;
- immutable snapshots;
- Tax transactions and reversals;
- Tax posting-profile concept.

**Critical gaps**

- Tax “posted” is independent from GL;
- Tax Finance contexts are not consumed atomically by all source workflows;
- Tax posting-profile scope semantics differ from Finance fallback semantics;
- period close is missing.

**Status:** Red.

---

## Tenant

**Strengths**

- shared-schema strategy explicitly enforced;
- tenant context and host policy;
- tenant plans, subscriptions, activation;
- queued tenant context restoration;
- domain verification design.

**Gaps**

- operational health can return a false positive;
- migration readiness checks only pending files, not schema compatibility;
- backup, worker, scheduler, mail, cache, storage, TLS, and alert health not proven.

**Status:** Amber/Red for production.

---

## UOM

**Strengths**

- conversion validation;
- exact decimal conversion;
- active/inactive rules;
- item base-UOM conversion workflow;
- historical document preservation.

**Gaps**

- no major confirmed blocker.

**Status:** Green.

---

## User

**Strengths**

- granular roles and permissions;
- tenant-scoped user access;
- escalation controls;
- session revocation;
- last-super-admin protection;
- immutable system roles.

**Gaps**

- frontend route-policy duplication can show actions the backend rejects;
- production access reviews and audit monitoring are operational processes, not proven.

**Status:** Green/Amber.

---

## Vehicle

**Strengths**

- vehicle master and relationships;
- ownership periods;
- status history;
- documents and attributes;
- tenant/organization isolation;
- row-version checks.

**Gaps**

- cross-module availability between Vehicle Service maintenance/off-road state and Vehicle Rental allocation is not fully proven end-to-end.

**Status:** Green/Amber.

---

## Vehicle Rental

**Strengths**

- lessor/lessee separation;
- effective-dated rates and allocations;
- custody and replacement;
- one physical running chart with independent commercial sides;
- approved-fact calculation;
- duplicate-consumption protection;
- deposit lifecycle;
- deterministic lock order;
- MySQL parity.

**Critical gaps**

- lessee/lessor financial documents can be posted without GL;
- vehicle-finance installment payable has the same gap;
- deposit receipt posts to the wrong control account under current Payment policy;
- financial reports are incomplete;
- replacement charging, downtime, free-KM pooling, accident excess, early termination, and deposit-priority rules require business decisions.

**Status:** Operationally strong, financially red.

---

## Vehicle Service

**Strengths**

- job, inspection, lines, employee assignment;
- parts issue;
- invoicing and payment links;
- partial invoicing;
- status and concurrency controls.

**Critical gaps**

- service Invoice is posted without Finance;
- Inventory issue does not uniformly post inventory/COGS Finance;
- external-work supplier payable/accounting workflow is not evidenced as complete.

**Status:** Operationally green, financially red.

---

## Voucher

**Strengths**

- presents Payment-owned immutable facts;
- does not create a second monetary lifecycle.

**Gaps**

- must remain a view/workspace only and never become a duplicate Payment owner;
- document numbering/printing permissions should continue to be tested against Payment status.

**Status:** Green/Amber.

---

## Warehouse

**Strengths**

- warehouse/location hierarchy;
- defaults;
- lifecycle;
- exact permissions;
- tenant/organization boundaries;
- concurrency/version checks.

**Gaps**

- no major confirmed blocker.

**Status:** Green.

---

# 8. Test-suite audit

## What the green suites prove

The current suites provide strong evidence for:

- syntax and container resolution;
- domain validations;
- tenant isolation;
- SQLite/MySQL schema parity;
- exact decimals;
- many service transactions;
- Invoice balances;
- Payment allocations and reversals;
- Inventory quantity/valuation;
- Vehicle Rental domain integrity;
- frontend unit/component contracts.

## What they do not prove

### TEST-001 — Static source tests are overused

Several architecture tests read source files and assert strings/imports.

These are useful regression guards but do not prove:

- the route boots;
- dependency injection resolves;
- the transaction commits or rolls back;
- the database lock order works under parallel requests;
- a posted source has a journal;
- reversal restores every dependent source.

### TEST-002 — Missing financial invariants

Required behavioral invariants:

1. Every posted accounting Invoice has exactly one active Finance journal.
2. Every reversed Invoice has exactly one Finance reversal.
3. Every posted Tax source has matching Tax and GL entries.
4. Every stock-value movement requiring accounting has a matching journal.
5. Every Payment type posts to its correct semantic control account.
6. Source total = journal economic total after Tax/withholding/adjustments.
7. Source reversal restores both subledger and ledger.
8. No source can post twice with alternate `source_module` text.

### TEST-003 — Contradictory/stale test name

The suite still reports both:

- activation requires execution and printable terms;
- agreement can be activated without printable terms.

Actual current rule is that execution context is required and printable clauses are optional.

The stale name is documentation debt inside the test suite.

### TEST-004 — Missing execution environments

Not currently proven:

- real parallel MySQL requests;
- browser E2E;
- accessibility;
- PHP static analysis;
- coverage thresholds;
- Composer/NPM security audit;
- queue and scheduler;
- Redis/cache;
- SMTP;
- object storage;
- large-report/load baseline;
- backup and restore;
- released-schema upgrade.

---

# 9. Documentation audit

## DOC-001 — Production-readiness overclaim

A historical readiness document calls the project a production-ready release candidate while also listing unverified TLS, backup, workers, scheduler, storage, secrets, and deployment checks.

Current source-level Finance blockers make that verdict stale.

## DOC-002 — Append-only change records lack an authoritative current-state index

Historical `/docs/changes` records are valuable evidence, but old conclusions remain searchable after later corrections.

Add a canonical index containing:

- current architecture decisions;
- superseded documents;
- current release head;
- unresolved blockers;
- current business-rule decisions;
- verified test evidence;
- operational verification evidence.

## DOC-003 — Video audits are business evidence, not executable requirements

The legacy video audits correctly preserve business knowledge but explicitly contain derived and unconfirmed rules.

Maintain a decision register:

| Rule | Status | Owner | Evidence | Effective date |
|---|---|---|---|---|
| Replacement vehicle charging | Unconfirmed | Business | Video insufficient | — |
| Downtime deduction | Unconfirmed | Business/Finance | Video insufficient | — |
| Deposit utilization priority | Unconfirmed | Finance/Operations | Video insufficient | — |
| Partial-month divisor | Confirmed in current calculation as calendar-day proration for supported mode | Product/Finance | Automated tests | Current |
| Free-KM pooling | Unconfirmed | Business | Video insufficient | — |

Do not convert unconfirmed video observations into code defaults.

## DOC-004 — Required canonical documents

Create and maintain:

1. `CURRENT_ARCHITECTURE.md`
2. `FINANCE_SOURCE_OWNERSHIP.md`
3. `FINANCE_POSTING_CATALOG.md`
4. `ACCOUNTING_PERIOD_POLICY.md`
5. `SCHEMA_MIGRATION_POLICY.md`
6. `BUSINESS_RULE_DECISION_REGISTER.md`
7. `RELEASE_READINESS_CURRENT.md`
8. `SOURCE_TO_GL_RECONCILIATION.md`
9. `MODULE_CAPABILITY_MATRIX.md`

---

# 10. Frontend and UX audit

## Access policy duplication

The frontend composes feature-owned route policies and then falls back to a large legacy registry.

The legacy registry still duplicates:

- Purchase;
- Payment;
- Vehicle Rental;
- Finance;
- Tax;
- Invoice;
- HR;
- Vehicle Service.

This is not the security authority—the backend remains authoritative—but it creates:

- hidden or visible action drift;
- confusing 403 responses;
- duplicated business permissions;
- stale comments and route ownership.

### Required correction

Complete migration to feature-owned entitlement registries and delete migrated rules from the legacy registry. Add a test that every routed tenant page has exactly one entitlement owner.

## Missing end-to-end browser coverage

Required browser journeys:

- tenant selection and organization context;
- user permission differences;
- PO → GRN → Invoice → Payment;
- Vehicle Service job → parts → Invoice → receipt;
- Vehicle Rental agreement → allocation → handover → running chart → customer/owner calculations → Invoice/payable → settlement;
- reversal journeys;
- stale row-version conflicts;
- print/PDF links;
- accessibility and keyboard navigation.

---

# 11. Operational-readiness audit

Current platform health proves only a subset of production readiness.

## Must be verified before go-live

- production `.env`:
  - `APP_ENV=production`;
  - `APP_DEBUG=false`;
  - HTTPS URLs;
  - secure refresh cookies;
  - correct trusted proxies;
  - protected secrets;
  - production database;
  - production mail;
  - production queue/cache/storage.
- queue:
  - broker connectivity;
  - active workers;
  - failed-job alerts;
  - retry/dead-letter policy.
- scheduler:
  - heartbeat;
  - overdue-job alert;
  - tenant jobs and finance due-status jobs.
- mail:
  - actual delivery test;
  - bounce/failure handling;
  - sender-domain authentication.
- storage:
  - write/read/delete;
  - tenant isolation;
  - private access;
  - backup;
  - malware/MIME controls.
- database:
  - migration rehearsal;
  - connection pool;
  - deadlock retry;
  - backups;
  - restore drill.
- monitoring:
  - application errors;
  - queue failures;
  - scheduler failures;
  - database health;
  - disk/object storage;
  - TLS expiry;
  - audit anomaly alerts.
- security:
  - dependency audits;
  - secret scan;
  - penetration test;
  - permission review;
  - log redaction.

---

# 12. Required remediation order

## Phase 0 — Stop incorrect release labeling

- Mark current state as **financially blocked**.
- Supersede stale production-readiness documents.
- Freeze new Finance-affecting feature work until ownership contracts are agreed.

## Phase 1 — Define the canonical Finance source contract

- Create a Finance posting catalogue by source type.
- Define semantic line keys for every current business workflow.
- Define which module owns each amount and reversal.
- Define whether a source is accounting or non-accounting.
- Remove compatibility aliases after callers migrate.

## Phase 2 — Implement accounting periods

- Finance-owned periods;
- close/reopen permissions;
- posting/reversal validation;
- Tax/report integration;
- audit trail.

## Phase 3 — Fix Invoice posting and reversal

- atomic Invoice + Tax + Finance post;
- journal reference on Invoice;
- exact once-only source identity;
- coordinated posted-Invoice reversal;
- source-module restoration hooks;
- no posted status without journal.

## Phase 4 — Fix Payment posting policies

- policy by type/direction/party/original payment;
- customer advance and deposit liability;
- refunds;
- supplier refunds;
- deposit application/forfeiture;
- dedicated tests for every payment type.

## Phase 5 — Complete Purchase/Inventory accounting

- generic GRN Finance posting;
- inventory issue/adjustment/transfer policies;
- GRNI clearing;
- generic supplier Invoice posting;
- source-aware reversal;
- Fast Purchase withholding fix;
- eliminate differences between Fast Purchase and normal Purchase paths.

## Phase 6 — Complete Tax atomicity

- Tax and Finance generated in the same source transaction;
- Tax reversal and journal reversal coordinated;
- Tax profile scope aligned with Finance scope;
- source-to-Tax-to-GL reconciliation.

## Phase 7 — Integrate Vehicle Service and Vehicle Rental

- service Invoice revenue/AR posting;
- parts issue inventory/COGS posting;
- external-work payable;
- rental lessee revenue;
- rental lessor cost/payable;
- vehicle finance principal/interest/fee;
- deposit semantic accounting;
- reversal tests.

## Phase 8 — Harden posting-profile configuration

- profile/assignment scope visibility;
- optimistic concurrency;
- strict shared date validation;
- service-owned scope protection;
- remove duplicate FinanceController methods;
- complete default semantic profile catalogue.

## Phase 9 — Add invariant tests

- source-to-journal;
- reversal;
- period close;
- all payment types;
- generic and Fast Purchase parity;
- Tax/GL;
- real parallel MySQL tests;
- browser E2E.

## Phase 10 — Schema and operations

- released-schema upgrade test;
- production-sized migration rehearsal;
- backup/restore;
- workers/scheduler/mail/cache/storage;
- monitoring and alerting;
- UAT and financial reconciliation.

## Phase 11 — Reporting, UI, and documents

- source-vs-GL exception report;
- Vehicle Rental financial reports;
- frontend entitlement single source;
- canonical current-state documents;
- stakeholder sign-off for unresolved business rules.

---

# 13. Mandatory go-live acceptance criteria

Production approval requires all of the following:

## Financial correctness

- every posted accounting source has exactly one active balanced journal;
- every reversed source has exactly one linked reversal journal;
- no active journal exists for a reversed source;
- Invoice subledgers reconcile to AR/AP;
- Inventory valuation reconciles to Inventory GL;
- Tax transactions reconcile to Tax GL;
- Payment types use correct control accounts;
- trial balance is balanced;
- source-to-GL exception report is empty or formally approved.

## Data lifecycle

- persistent schema upgrade rehearsal passes;
- rollback/restore plan is tested;
- backup restore is proven;
- historical snapshots remain unchanged;
- tenant and organization isolation passes.

## Operations

- worker and scheduler heartbeat;
- SMTP delivery;
- cache and storage;
- TLS and secrets;
- monitoring and alerts;
- log redaction;
- production health is behavioral, not configuration-only.

## Product and QA

- critical browser E2E journeys pass;
- concurrent allocation/posting tests pass;
- financial UAT signed by Finance/business owners;
- unresolved rental rules are either decided or explicitly disabled;
- release documents identify the exact commit and evidence.

---

# 14. Final conclusion

AutoERP is not a weak project. Its domain and integrity foundations are stronger than the green-test count alone suggests.

The current principal weakness is architectural: **Finance ownership is correct inside Finance, but incomplete at the boundaries where business sources become financial truth**.

The project should not be rewritten. The correct path is to retain the strong module foundations and fix the source-to-ledger contract at its owning boundaries.

Until Invoice posting/reversal, Payment classification, accounting periods, generic Purchase/Inventory posting, Tax atomicity, and migration/operational gates are corrected and verified, the system must not be approved for production financial use.
