# AutoERP Independent End-to-End Deep Audit

**Audit date:** 2026-07-12  
**Repository:** `kasunvimarshana/AutoERP`  
**Authoritative branch:** `worktree-0.0.8`  
**Current audited head:** `31776804bfc01177a9e3bc072a9a9f0e0ab42de4`  
**Production-code baseline:** `314950e6f20b00eb9b22e91470b6c64631ffd495`  
**Difference from production-code baseline:** one documentation-only commit adding the prior audit report and issue register.  
**Paid tools / GitHub Actions:** not used.  
**Code modifications:** none.

---

## 1. Executive verdict

AutoERP is not a weak or unstructured project. Its strongest foundations are:

- shared-schema tenant and organization-unit isolation;
- exact base-10 decimal arithmetic;
- controlled domain services and explicit data contracts;
- immutable Finance journal and ledger mechanics;
- Invoice source/source-line allocations and balances;
- Payment instruments, allocations, refunds, reversals, and row-version checks;
- Inventory quantity, reservation, serial, batch, and valuation controls;
- Vehicle Rental agreements, rate versions, allocations, custody, running charts, calculations, and deposit controls;
- broad SQLite/MySQL automated test parity previously supplied by the project owner.

However, the project is **not ready for production financial operations**.

The root problem is architectural, not a failing-test count:

> Finance is internally strong, but the business-source → financial-document → Tax → Finance journal → reversal chain is incomplete and inconsistent.

A business document can currently become operationally or financially “posted” without proving that the corresponding balanced General Ledger journal exists. Some paths post GL, some do not, and later generic reversal paths do not consistently reverse journals created by special fast paths.

### Current release classification

| Area | Verdict |
|---|---|
| Source-code quality | Conditional pass |
| Local automated suites previously supplied | Pass |
| SQLite/MySQL parity previously supplied | Pass |
| Operational workflow foundation | Strong in many modules |
| Finance source ownership | Blocked |
| Financial UAT | Not ready |
| Production financial go-live | Blocked |
| Infrastructure/operations readiness | Not verified |

---

## 2. Audit scope and method

The review covered all 28 registered application modules:

1. Audit
2. Auth
3. Configuration
4. Core
5. Customer
6. Finance
7. HR
8. Inventory
9. Idempotency
10. Invoice
11. Item
12. Organization Unit
13. Payment
14. Private Object
15. Purchase
16. Reference Data
17. Reporting
18. Sequence
19. Supplier
20. Tax
21. Tenant
22. UOM
23. User
24. Vehicle
25. Vehicle Rental
26. Vehicle Service
27. Voucher
28. Warehouse

The audit cross-checked:

- module registration and route ownership;
- owner services and cross-module integrations;
- lifecycle/state transitions;
- tenant and organization scoping;
- row-version and locking behavior;
- financial source identity;
- posting profiles, account roles, assignments, and effective dates;
- Invoice, Payment, Purchase, GRN, Inventory, Tax, Vehicle Service, Vehicle Rental, and Vehicle Finance flows;
- migrations and persistent-schema strategy;
- automated tests and what they do or do not prove;
- frontend permission-policy ownership;
- operational health/readiness;
- historical documentation and video-derived business knowledge.

The prior audit documents were used only as an index. Critical conclusions were independently rechecked against current production source.

### Not executed in this environment

- full Laravel/PHPUnit suites;
- MySQL/MariaDB suites;
- TypeScript, ESLint, Vite, or Vitest;
- live browser E2E;
- real parallel/concurrent transactions;
- queues and scheduler;
- SMTP;
- Redis/cache;
- production object storage;
- backup/restore;
- production migration rehearsal;
- dependency vulnerability scans;
- penetration testing.

Previously supplied evidence reports 659 Laravel tests / 8,270 assertions on SQLite and the same result on MySQL, plus passing frontend typecheck, lint, build, and 254 Vitest tests at the production-code baseline. Those results were not rerun in this audit environment.

---

## 3. Canonical ownership model

### Business/source module owns

- commercial and operational meaning;
- source aggregate and source-line identity;
- quantities and economic amounts;
- source lifecycle;
- semantic posting keys;
- source-specific reversal eligibility and restoration.

### Invoice owns

- receivable/payable document lifecycle;
- immutable document snapshots;
- balances and settlement eligibility;
- source/source-line allocation;
- governed posting and reversal coordination.

### Payment owns

- payment instruments and method snapshots;
- allocations and unapplied balances;
- refunds and payment reversal;
- payment-type semantic posting selection;
- invocation of Finance in the same transaction.

### Tax owns

- tax determination;
- tax snapshots;
- Tax transactions;
- Tax reversal facts;
- semantic tax posting lines.

### Finance owns

- account roles and account assignments;
- posting profiles and effective-dated mapping;
- accounting-period validation;
- balanced journal creation;
- immutable ledger entries;
- source-journal idempotency;
- journal reversal;
- financial reports.

### Boundary rule

Business modules must not choose GL accounts by code or account ID. Finance must not invent business amounts, party meaning, or source restoration behavior.

---

# 4. Critical release blockers

## P0-FIN-001 — An Invoice can be Posted without a Finance journal

### Confirmed behavior

`InvoiceStatusService` allows `Approved → Posted`. The Posted transition:

- updates Invoice status and posted metadata;
- posts Tax snapshots/transactions;
- does not invoke `FinancePostingInterface`;
- does not store a Finance journal identity on the Invoice.

`InvoiceCreationService` can create a requested Posted Invoice inside its transaction by delegating to `InvoiceIssuanceService`, which performs Approved then Posted transitions. It still does not post Finance.

`InvoiceFinanceIntegrationService` exists, but only prepares and validates a posting request. It does not post a journal, and repository usage is limited to the service and its test. It is not integrated into the actual Invoice lifecycle.

### Affected flows

- manual Invoice;
- generic outbound/customer Invoice;
- generic supplier Invoice;
- Vehicle Service Invoice;
- Vehicle Rental lessee Invoice;
- Vehicle Rental owner/lessor payable represented as an inbound Invoice;
- Vehicle Finance installment payable;
- any caller requesting a Posted Invoice from `InvoiceCreationService`.

### Impact

- AR/AP subledgers can diverge from GL control accounts;
- posted Tax can exist without Tax GL;
- revenue, expense, assets, and liabilities can be understated;
- Payment may clear AR/AP that was never established;
- trial balance may still balance while business-source accounting is incomplete.

### Required correction

Create one Invoice-owned posting command:

```text
Lock Invoice and source facts
→ validate accounting period
→ validate accounting classification
→ build source-owned semantic posting plan
→ post Tax and Finance exactly once
→ store Finance journal reference
→ mark Invoice Posted
→ commit one transaction
```

A Posted accounting Invoice must not exist without exactly one active balanced journal.

---

## P0-FIN-002 — No coordinated posted-Invoice reversal

Posted, Partially Paid, and Paid Invoice transitions do not include a governed reversed state. Posted Invoices cannot be cancelled/voided through the current state machine.

Finance can reverse a journal directly, but a manual Finance reversal does not coordinate:

- Invoice status/balance;
- settlement allocations;
- Tax reversal;
- Purchase/GRN source quantities;
- Vehicle Service job status;
- Rental calculation consumption;
- Vehicle Finance installment state;
- source-module audit events.

### Required correction

Implement an Invoice-owned reversal coordinator with source-module restoration hooks:

```text
Invoice lock
→ validate no prohibited settlement state
→ source owner confirms reversal eligibility
→ Finance journal reversal
→ Tax reversal
→ Invoice balance/status reversal
→ source allocation restoration
→ source status synchronization
→ immutable audit event
→ commit one transaction
```

---

## P0-FIN-003 — Payment posting is selected by direction, not economic meaning

The Payment domain supports:

- supplier payment;
- customer receipt;
- service receipt;
- rental receipt;
- advance;
- refund;
- manual payment.

Current posting logic uses only direction:

```text
Inbound  → Dr Cash/Bank, Cr Receivable
Outbound → Dr Payable, Cr Cash/Bank
```

The posting profile code is only `payment_received` or `payment_made`.

### Confirmed misclassifications

- inbound customer/rental security deposit credits AR instead of a customer-deposit/advance liability;
- outbound customer refund debits AP;
- inbound supplier refund credits AR;
- an unallocated customer advance is accounted the same as an allocated invoice receipt;
- refunds do not inherit/reverse the original payment’s economic classification.

### Required correction

Introduce a Payment-owned posting policy selected by:

```text
payment_type
+ direction
+ party_type
+ allocation state
+ original payment classification for refunds
```

Required semantic policies include:

- customer receipt;
- supplier payment;
- customer advance receipt;
- rental/customer deposit receipt;
- customer refund;
- supplier refund;
- advance application;
- deposit application;
- deposit forfeiture;
- explicitly governed manual transfer.

---

## P0-FIN-004 — Accounting periods do not exist

Finance validates that the posting date is non-empty, the exchange rate is positive, accounts are active/postable, and lines balance. It does not validate the posting or reversal date against an accounting period.

There are no:

- accounting-year/period masters;
- Open / Soft Closed / Closed / Reopened states;
- close/reopen commands;
- period permissions;
- period validation in posting/reversal;
- Tax/report freeze integration.

### Impact

Users can backdate or reverse transactions into already reported periods, silently changing:

- trial balance;
- P&L;
- balance sheet;
- AR/AP;
- Tax liability;
- bank reconciliation;
- management reports.

Finance must own period policy. Source modules must not implement separate period checks.

---

## P0-DB-001 — Persistent database upgrade lifecycle is unresolved

The project strongly favors clean baseline migrations and, in important modules, source-contract tests assume a fixed `Schema::create` baseline.

At the same time, operational deployment relies on normal `migrate --force` behavior. Editing an already-ran baseline migration does not update a persistent database.

### Required decision

If no persistent production data exists:

1. declare a schema freeze/baseline release;
2. rebuild staging/production from the frozen baseline;
3. never edit released baseline migrations afterward.

If persistent data exists:

1. introduce project-wide forward-only upgrade migrations;
2. version the released schema;
3. test previous-release schema → current schema;
4. rehearse on a restored production-sized copy;
5. reconcile row counts, foreign keys, balances, and source links.

---

# 5. Finance and posting-profile deep audit

## 5.1 Strong foundations to retain

`FinancePostingService` correctly provides:

- semantic profile keys rather than caller-selected account IDs;
- exact decimal normalization;
- balanced posting validation;
- active/postable account validation;
- source-key idempotency;
- posting fingerprint conflict detection;
- draft creation and posting;
- immutable journal reversal;
- replay safety.

Posting configuration correctly separates:

```text
Posting profile
→ effective-dated semantic rule
→ account role
→ effective-dated role/account assignment
→ Finance account
```

Organization-specific runtime resolution falls back to a tenant-level profile/assignment.

These foundations should be retained rather than rewritten.

## 5.2 P1-FIN-011 — Runtime fallback is hidden or inconsistent in configuration APIs

Runtime profile resolution supports:

```text
organization-specific profile
→ tenant-level fallback
```

Configuration endpoints are inconsistent:

- profile listings return exact organization scope only;
- account-assignment listings include organization and tenant fallback rows;
- ending an assignment requires exact organization scope;
- the profile resource does not expose `organization_unit_id`, effective source scope, or fallback origin.

An administrator can therefore be unable to see the profile actually used at runtime, or can see a fallback assignment that cannot be ended from the same organization context.

### Required correction

Expose:

- stored scope;
- effective scope;
- fallback origin;
- effective rule/account as of a selected posting date.

Do not silently flatten fallback configuration into one list.

## 5.3 P1-FIN-012 — Posting configuration lacks optimistic concurrency

Posting profiles, rules, roles, and assignments do not use a consistent:

- `row_version`;
- `expected_version`;
- conflict-safe update command.

Two administrators can edit the same profile or effective-dated mapping concurrently.

Posting-profile overlap checking is application-level and does not lock a canonical parent row before checking/saving. The database uniqueness key protects only the same profile/key/effective-from date, not arbitrary overlapping date ranges.

### Required correction

- version the profile aggregate;
- lock the profile before rule mutation;
- require `expected_version`;
- perform overlap validation under the lock;
- use a database-supported invariant where practical;
- version account-role and assignment mutations consistently.

## 5.4 P1-FIN-017 — Posting-profile update retains omitted active rules

`PostingProfileService::save()` iterates and upserts supplied rules, but it does not delete, end-date, or deactivate existing rules omitted from an update payload.

Therefore a user can remove a rule in the UI/request and still have the previous active mapping used at runtime.

This is not ordinary documentation debt. It can create incorrect journal account resolution.

### Required correction

Choose and document one command semantic:

- **replace aggregate:** omitted rules are explicitly ended/deactivated in the same transaction; or
- **append-only effective-dated mutation:** provide explicit add/end/deactivate commands and prohibit a misleading full-profile replacement request.

Do not silently retain omitted rules.

## 5.5 P2-FIN-016 — Owner service can relocate an existing posting profile

`PostingProfileService::save()` force-fills `tenant_id` and `organization_unit_id` even for an existing model. The controller currently scopes the model, but the owner service itself does not prohibit moving scope.

The owner service must enforce immutable tenant/organization identity.

## 5.6 P2-FIN-015 — Duplicate obsolete Finance configuration code remains

Routes use `FinanceConfigurationController`, but `FinanceController` still contains older:

- lookups;
- posting profile list/create/update methods.

The old implementation expects `rules.account`, while the current design uses `rules.role`.

This is dead code in the wrong owner and a future regression risk. Remove it; do not preserve it as a compatibility path.

## 5.7 P2-FIN-013 / P2-FIN-014 — Manual journal source spoofing and caller-controlled source identity

The public manual journal request accepts:

- `source_module`;
- `source_type`;
- `source_id`;
- source number/date;
- source-line identities.

`JournalEntryCreationService` persists these values without validating them against a registered business source.

`FinancePostingService` also includes caller-provided `source_module` in the source key. The same source type/id can potentially be presented with a different module string and receive another source key.

### Required correction

- manual journals must use a canonical `manual_journal` source classification;
- registered business source identities must be created only through source-owned integration commands;
- use a canonical source registry or typed source identity;
- do not let arbitrary manual input impersonate Invoice, Payment, Purchase, or another module;
- exclude free-form caller aliases from exact-once source identity.

## 5.8 Seeded posting-profile coverage is incomplete

Current defaults cover basic:

- sales Invoice;
- purchase Invoice;
- payment received/made;
- inventory receipt/issue;
- vehicle service Invoice;
- sales/purchase returns.

Missing semantic coverage includes:

- customer advance liability;
- rental/customer deposit liability;
- customer refund;
- supplier refund;
- deposit application;
- deposit forfeiture;
- rental income components;
- lessor/owner rental expense components;
- vehicle-finance principal, interest, fees, and Tax;
- realized/unrealized FX gain/loss;
- bank fees;
- payroll/statutory liabilities if payroll enters scope;
- workshop issue/COGS variations where required.

Do not add hardcoded account codes in business modules. Add semantic roles/profiles and block workflows when required configuration is incomplete.

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
| Inventory receipt/issue/adjustment/transfer | Inventory | Not uniform | Not uniform | High |
| Customer receipt | Payment | Present | Present | Mapping risk |
| Supplier payment | Payment | Present | Present | Strong |
| Customer/rental deposit receipt | Vehicle Rental + Payment | Present | Present | Wrong control account |
| Payment refund | Payment | Present | Present | Wrong classification risk |
| Vehicle Service Invoice | Vehicle Service + Invoice | Missing | Missing | Critical |
| Vehicle Rental lessee Invoice | Vehicle Rental + Invoice | Missing | Missing | Critical |
| Vehicle Rental owner payable | Vehicle Rental + Invoice | Missing | Missing | Critical |
| Vehicle Finance installment payable | Vehicle Rental + Invoice | Missing | Missing | Critical |
| Tax snapshot/transaction | Tax | Tax facts only | Tax facts only | High |
| Manual Finance journal | Finance | Present | Present | Source identity risk |
| Payment reversal | Payment + Finance | Present | Present | Strong |

---

# 7. Purchase and Inventory findings

## P1-FIN-005 — Generic Purchase paths do not match Fast Purchase accounting

Fast Purchase has a strong outer transaction coordinating:

- idempotency;
- PO;
- GRN;
- supplier Invoice;
- Finance postings;
- Payment;
- audit.

Generic Purchase does not have the same Finance lifecycle:

- generic supplier Invoice calls `InvoiceCreationService` and links quantities, but does not post Finance;
- generic GRN posts Inventory and Tax, but does not post Finance;
- generic GRN reversal reverses Inventory and Tax, but does not reverse Finance.

This creates two different accounting outcomes for the same business process depending on which UI/API path was used.

## P1-FIN-010 — Fast Purchase withholding is posted to ordinary AP

Fast Purchase creates a line described as “Withholding payable” but maps it to the ordinary `payable` profile key.

The seeded purchase profile already contains a distinct `withholding_payable` role.

Impact:

- supplier AP control is overstated;
- withholding liability is understated/mixed;
- supplier statements and Tax reconciliation diverge.

## P1-FIN-006 — Inventory value changes are not uniformly represented in GL

Inventory has strong physical/valuation controls, but stock-value movements do not consistently create/reverse Finance journals for:

- receipt;
- issue;
- adjustment;
- transfer consequences;
- workshop parts issue/COGS;
- purchase returns and reversal variants.

Operational inventory can be correct while Inventory GL is wrong.

---

# 8. Tax findings

## P1-FIN-009 — Tax “posted” is independent of Finance

Tax posting:

- creates Tax transactions from snapshots;
- marks snapshots `posted = true`;
- does not require a Finance journal.

Tax reversal:

- creates negative Tax snapshots and transactions;
- marks them posted;
- does not coordinate a Finance reversal.

Tax can build posting contexts, but not all source workflows consume them atomically.

### Required correction

Tax facts and GL tax lines must be generated within the source owner’s governed posting/reversal transaction, with source-to-Tax-to-GL reconciliation.

---

# 9. Vehicle Service and Vehicle Rental findings

## Vehicle Service

Strengths:

- job and line ownership;
- inspections;
- employee assignments;
- partial invoicing;
- version checks;
- source snapshots and Invoice links.

Critical gaps:

- service Invoice is transitioned to Posted without Finance;
- parts issues do not uniformly post Inventory/COGS;
- external-work supplier payable/accounting is not evidenced as a complete lifecycle.

## Vehicle Rental

Strengths:

- lessor and lessee agreements are separate;
- effective-dated rate versions and allocations;
- custody and replacement;
- one physical running chart with separate customer and owner facts;
- approved-fact calculations;
- duplicate same-side consumption prevention;
- deposit lifecycle;
- row-version and locking controls;
- SQLite/MySQL parity previously reported.

Critical gaps:

- customer Invoice and owner payable can be Posted without GL;
- vehicle-finance installment payable has the same gap;
- deposit receipt uses the incorrect generic Payment control account;
- rental financial and reconciliation reports are incomplete;
- source-aware reversal is incomplete.

Business decisions still required before implementation:

- replacement vehicle charging;
- downtime/off-road deductions;
- free-KM pooling;
- garage mileage billing;
- accident/insurance excess;
- early termination penalties;
- deposit-utilization priority;
- multi-driver splits.

Legacy videos are business evidence, not permission to guess these rules.

---

# 10. Other module findings

## Audit — Green/Amber

Strong append-only design and scoped recording. Operational retention, archival, export, monitoring, alert delivery, and cross-module completeness are not proven.

## Auth — Green/Amber

Strong tenant/platform separation, token/session controls, and revocation patterns. Production rate limiting, key rotation, trusted proxy/TLS behavior, brute-force monitoring, and penetration testing are not verified.

## Configuration — Green/Amber

Strong global/tenant/organization precedence. Finance posting configuration has separate fallback semantics and UX, creating configuration-source drift.

## Core — Green

Strong decimal math, typed contracts, execution context, and error handling. Static architecture tests cannot prove runtime transactions/concurrency.

## Customer — Amber

Credit policy has an authoritative separate profile. Invoice posting does not centrally prove enforcement of credit allowance, limit, overdue/on-hold policy. Customer-advance accounting is incomplete.

## HR — Amber / scope decision

Employee, skills, rates, certifications, and availability exist. Payroll, leave, attendance, statutory liabilities, payroll periods, payslips, and payroll Finance posting are not implemented. Confirm scope before adding a Payroll module.

## Idempotency — Green/Amber

Strong in Fast Purchase. Stale-in-progress recovery and real parallel behavior are not proven across all critical commands.

## Item — Green

Strong variants, UOM, bundles, categories, brands, and effective price revisions. Large-catalogue performance needs load testing.

## Organization Unit — Green/Amber

Strong context and hierarchy controls. Finance/config fallback visibility needs correction.

## Private Object — Amber / not verified

Path-traversal protection and private abstraction exist. MIME/content validation, malware scanning, at-rest encryption requirements, signed URL expiry, retention/legal hold, orphan cleanup, and production backup are not proven.

## Reference Data — Green

Shared reference masters are well separated. Historical meaning must continue to be snapshot-based.

## Reporting — Amber/Red

Export foundations are strong, but reports are only as accurate as GL source coverage. Missing:

- source-vs-GL exception report;
- complete rental financial reports;
- large-volume performance baseline;
- scheduled delivery/archival proof.

## Sequence — Green/Amber

Strong tenant-scoped monotonic generation and conflict handling. Production throughput under high concurrency is not tested.

## Supplier — Amber

Strong master, credit, bank, item mapping, and history. Supplier advance/refund accounting is incomplete and AP depends on Invoice posting.

## Tenant — Amber/Red operationally

Good tenant context, plans, onboarding, domain handling, and queued context restoration.

The health service’s `ready` result currently proves only:

- an external-looking mailer configuration;
- a non-sync queue configuration;
- no pending migration filenames.

It does not prove:

- actual SMTP delivery;
- active worker;
- scheduler heartbeat;
- broker/cache connectivity;
- storage read/write;
- backup freshness/restore;
- TLS;
- alerting;
- schema compatibility.

## UOM — Green

Strong exact conversion and historical preservation.

## User — Green/Amber

Strong backend roles/permissions, last-admin protection, session revocation. Frontend route policy duplication can expose actions later rejected by API.

## Vehicle — Green/Amber

Strong ownership, status history, documents, attributes, and version checks. End-to-end rental availability vs workshop maintenance/off-road state needs proof.

## Voucher — Green/Amber

Correctly presents Payment-owned facts. Must remain a view/print workspace and never become a second payment lifecycle owner.

## Warehouse — Green

Strong warehouse/location lifecycle, defaults, permissions, and version checks.

---

# 11. Model and relationship correctness

## P2-INV-001 — Invoice party relationships are type-ambiguous

Invoice stores `party_type` and `party_id`, but exposes both:

- `customer()` using `party_id`;
- `supplier()` using the same `party_id`.

Customer and Supplier IDs can overlap. A caller loading the wrong relationship can receive an unrelated record with the same numeric ID.

Use a type-aware party resolver or guarded relationship boundary. Do not rely on callers remembering which relation is valid.

---

# 12. Test-suite audit

## What previous green suites prove

- many domain validations;
- tenant isolation;
- SQLite/MySQL schema parity;
- exact decimals;
- Invoice balances;
- Payment allocations/reversals;
- Inventory quantity/valuation;
- Vehicle Rental integrity;
- frontend unit/component behavior.

## What they do not prove

- every Posted source has a Finance journal;
- every reversal reverses source, Tax, subledger, and GL;
- route/DI behavior for tests that only inspect source text;
- real MySQL deadlock behavior;
- browser workflows;
- production infrastructure;
- released-schema upgrade;
- backup/restore;
- penetration/security posture.

## Required financial invariant tests

1. Every Posted accounting Invoice has exactly one active journal.
2. Every reversed Invoice has exactly one linked reversal journal.
3. Every Posted Tax source has matching Tax and GL entries.
4. Every accounting-required stock value movement has a journal.
5. Every Payment type resolves to its correct semantic control account.
6. Source economic total equals journal economic total.
7. Source reversal restores subledger and ledger.
8. Altering `source_module` cannot bypass exact-once source identity.
9. Generic Purchase and Fast Purchase produce equivalent accounting.
10. Omitted posting-profile rules cannot remain silently effective.
11. Profile updates reject stale row versions.
12. Closed periods reject posting and reversal.
13. Parallel MySQL posting/allocation commands preserve invariants.

## Test-design issue

Source-text and import-string tests are useful architecture guards, but they are not behavioral proof. Critical guards must be paired with real service/API/database tests.

---

# 13. Documentation audit

## Stale or overstated readiness claims

Historical documents can call the project a production-ready candidate while also listing unverified TLS, backup, worker, scheduler, storage, secrets, and deployment steps.

The current Finance blockers make those claims stale.

## Append-only documents lack supersession

Historical `/docs/changes` evidence is valuable, but older conclusions remain searchable after later fixes.

Create a canonical current-state index containing:

- exact release commit;
- current architecture;
- superseded documents;
- open blockers;
- verified tests;
- business decisions;
- operational evidence.

## Video audits are not executable requirements

Legacy videos confirm important business scope:

- lessor/lessee agreements;
- running charts;
- customer billing;
- owner settlement;
- receipts/payments;
- GL and reconciliation;
- workshop job/parts/outside work/labour/invoice flow.

They do not prove every formula or policy. Unconfirmed video observations must remain in a decision register, not become hidden defaults.

## Required canonical documents

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

# 14. Frontend and UX audit

The backend remains the security authority, but frontend feature policies still coexist with legacy fallback rules.

Risks:

- an action appears but API returns 403;
- valid routes become hidden;
- permissions are duplicated and drift;
- module ownership is unclear.

Required correction:

- one feature-owned entitlement registry;
- exactly one entitlement owner per routed tenant page;
- remove migrated rules from the legacy registry;
- keep API authorization authoritative.

Required browser journeys:

- tenant and organization context;
- permission differences;
- PO → GRN → Invoice → Payment;
- service job → parts → Invoice → receipt;
- rental agreement → allocation → custody → running chart → customer/owner calculations → Invoice/payable → settlement;
- reversal;
- stale-version conflicts;
- print/PDF;
- accessibility and keyboard navigation.

---

# 15. Operational readiness

Before go-live, verify behavior—not only configuration—for:

- production database and schema compatibility;
- migration rehearsal;
- backup and restore;
- queue broker and active workers;
- failed-job alerting;
- scheduler heartbeat;
- SMTP delivery and bounce/failure behavior;
- cache/Redis;
- private storage read/write/delete and tenant isolation;
- MIME/malware controls;
- TLS and trusted proxies;
- secret scanning;
- dependency audits;
- monitoring and alerts;
- log redaction;
- production-sized report/load baseline.

---

# 16. Correct remediation order

## Phase 0 — Correct release status

- mark financial production as blocked;
- supersede stale readiness claims;
- freeze new Finance-affecting features until source ownership is agreed.

## Phase 1 — Canonical Finance source contract

- define all accounting and non-accounting source types;
- define owner module, amounts, line keys, and reversal owner;
- define canonical source identity;
- create the Finance posting catalogue;
- remove compatibility aliases after migration.

## Phase 2 — Accounting periods

- Finance-owned period master;
- Open / Soft Closed / Closed / Reopened;
- permissions and audit;
- posting/reversal checks;
- Tax/report integration.

## Phase 3 — Invoice posting and reversal

- atomic Invoice + Tax + Finance posting;
- journal reference on Invoice;
- exactly-once source identity;
- Invoice reversal coordinator;
- source restoration hooks;
- no Posted state without GL.

## Phase 4 — Payment semantic posting

- policy by type/direction/party/allocation/original payment;
- advances and deposits;
- customer/supplier refunds;
- deposit application and forfeiture;
- tests per payment type.

## Phase 5 — Purchase and Inventory

- generic GRN Finance posting;
- generic supplier Invoice posting;
- inventory receipt/issue/adjustment/transfer policies;
- GRNI clearing;
- source-aware reversal;
- fix Fast Purchase withholding;
- generic/Fast Purchase parity.

## Phase 6 — Tax atomicity

- Tax and Finance in the source transaction;
- Tax/GL reversal coordination;
- scope alignment;
- reconciliation report.

## Phase 7 — Vehicle Service and Vehicle Rental

- service revenue/AR;
- parts issue inventory/COGS;
- outside-work payable;
- rental customer revenue;
- rental owner cost/payable;
- vehicle-finance principal/interest/fees;
- deposit semantics;
- reversal tests.

## Phase 8 — Posting-profile hardening

- scope/fallback visibility;
- optimistic concurrency;
- strict shared effective-date value;
- immutable profile scope;
- explicit rule end/deactivation;
- remove duplicate controller methods;
- complete semantic catalogue.

## Phase 9 — Invariant and E2E tests

- source-to-journal;
- reversal;
- periods;
- all Payment types;
- Purchase path parity;
- Tax/GL;
- parallel MySQL;
- browser E2E.

## Phase 10 — Schema and operations

- released-schema upgrade test;
- production migration rehearsal;
- backup/restore;
- workers/scheduler/mail/cache/storage;
- monitoring;
- UAT and financial reconciliation.

---

# 17. Mandatory go-live criteria

## Financial

- every Posted accounting source has exactly one active balanced journal;
- every reversed source has exactly one linked reversal journal;
- no active original journal remains economically unreversed for a reversed source;
- Invoice subledger reconciles to AR/AP;
- Inventory valuation reconciles to Inventory GL;
- Tax transactions reconcile to Tax GL;
- Payment types use correct control accounts;
- trial balance is balanced;
- source-to-GL exception report is empty or formally approved.

## Data lifecycle

- persistent schema upgrade rehearsal passes;
- backup restore is proven;
- historical snapshots remain immutable;
- tenant and organization isolation passes.

## Operations

- worker and scheduler heartbeat;
- real SMTP test;
- cache and storage test;
- TLS and secrets verified;
- monitoring and alerts;
- log redaction.

## Product and QA

- critical browser journeys pass;
- parallel posting/allocation tests pass;
- Finance/business UAT signed;
- unresolved rental rules decided or explicitly disabled;
- release documents identify exact commit and evidence.

---

# 18. Final conclusion

AutoERP should not be rewritten. The project has strong domain, transaction, decimal, tenant, and lifecycle foundations.

The correct correction is focused:

> Keep each module’s business ownership, but complete the governed source-to-ledger contract at the Invoice, Payment, Tax, Purchase/Inventory, Vehicle Service, and Vehicle Rental boundaries.

Until Invoice posting/reversal, Payment classification, accounting periods, generic Purchase/Inventory posting, Tax atomicity, posting-profile correctness, persistent schema strategy, and operational gates are corrected and verified, the project must not be approved for production financial use.
