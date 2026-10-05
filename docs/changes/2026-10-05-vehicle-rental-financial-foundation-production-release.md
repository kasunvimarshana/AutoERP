# Vehicle Rental financial foundation production release

**Date:** 2026-10-05

**Authoritative branch at review start:** `worktree-0.0.8` at `70d6fe4f559897a6190eb913cb283c04e83658fe`

**Implementation branch:** `agent/vehicle-rental-financial-foundation-20261005`

## Scope

This release continues the fresh Vehicle Rental implementation without restoring, copying, referencing, or adapting removed legacy Rental runtime code.

The Vehicle Rental schema, agreement model, vehicle-use/custody relationships, Running Chart lifecycle, independent customer/owner calculations, and source-consumption rules were re-reviewed first. No evidence-backed Rental table or relationship redesign was justified. The remaining end-to-end defects were found in the modules that own accounting, invoice/payment settlement, and consolidated reporting, so they are fixed there rather than by adding Rental workarounds.

The implementation acceptance ledger in `docs/vehicle-rental/TODO.md` remains closed.

## Source reconciliation and protected backup

The registered TACGL/video evidence remains unchanged:

- canonical `TACGL.zip`: SHA-256 `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`;
- corroborating `TACGL.rar`: SHA-256 `0001a91ab4dfdf31d60f669b30f1ccc2da475d7841b2ceea505ef97eff5bedbf`;
- dated `TACGL(20261005-130217).zip`: SHA-256 `79c240494943437978754169c3360bb7c6e35d911ef8263c4b2d6b6246384d77`;
- canonical and dated ZIPs: 452 normalized business files with the same path set and per-file payload; normalized manifest digest `97ae9e8de11687bb61f6c0125a8214da3fa02363156fc09faa91416e68b7a0a1`;
- all four registered Vehicle Rental video hashes remain unchanged.

The protected nested TACGL backup was investigated only through legitimate, non-destructive inspection of accessible source/configuration material. No explicit archive password was found. No brute force, credential reuse, paid recovery tool, or guessed password was used. The inaccessible archive remains non-evidence and did not block implementation.

## Root-cause findings

The fresh Rental module already delegated financial documents and money movement to Invoice, Payment, Finance, Tax, and Reporting. The completion review found that those owner modules still had cross-cutting gaps that could make an otherwise valid Rental journey financially inconsistent:

1. Finance journal headers froze `currency_id` and `exchange_rate`, but ledger/account-balance consumers treated transaction-currency debit/credit as though they were base currency.
2. Consolidated dashboard/report paths could aggregate unlike currencies directly.
3. Payment creation/settlement UI did not expose a complete existing-payment allocation workflow or an operator-driven instrument settlement workflow.
4. Payment creators could need Payment Method setup-view permission merely to retrieve usable methods.
5. Instrument settlement dates were derived from server “today” instead of the real business event date.
6. Invoice, Payment, and Finance did not consistently enforce the tenant-base-currency rate-one invariant.
7. Dashboard and generic aging calculations could use process-local “today” instead of the configured tenant/organization business timezone.
8. Supplier-payables dashboard drilldown targeted a GRN-specific report and could omit canonical inbound Invoice payables such as Rental owner settlements.

These are owner-module defects. No Rental-specific ledger, copied payment state, compatibility relationship, or hidden financial rule was introduced.

## Finance foundation

Finance now keeps two explicit monetary layers:

- journal and ledger `debit` / `credit` remain immutable **transaction-currency evidence**;
- ledger `base_debit` / `base_credit` are calculated exactly once from the frozen journal exchange rate and are the source for base-currency accounting projections.

Two guarded Finance upgrade migrations add/backfill the base columns, rebuild the base-currency running projection, and rebuild `finance_account_balances`. Their data-rebuild stages are transactional. Rollback restores the former transaction-currency projection before removing the new columns.

Runtime changes keep account balances, trial balance, P&L/balance-sheet inputs, Finance cash flow, budget actuals, and bank-reconciliation matching on base-currency ledger facts. Finance detail surfaces show transaction amounts and base amounts separately instead of silently conflating them.

Currency validation now fails closed:

- an explicit exchange rate must be positive;
- a document/journal without an explicit currency uses rate `1.000000`;
- an explicitly selected tenant base currency uses rate `1.000000`;
- selected currencies must be active;
- no market rate is guessed.

Currency-revaluation behavior was reviewed against this model. Revaluation postings are base-currency adjustment differences and continue to post at unit exchange rate by default, so the base-ledger conversion does not re-convert the adjustment.

## Invoice and Payment completion

Invoice applies the same base-currency exchange-rate invariant at document creation, preventing an invalid draft from surviving until Finance posting.

Payment now provides a least-privilege usable-method lookup authorized by `payments.create`; setup administration remains governed by the existing Payment Method permissions.

Invoice settlement in Payment requires the operator to enter/verify the **payment-date accounting exchange rate**. The UI does not copy the Invoice's earlier exchange rate into the Payment.

Existing posted unapplied payments now expose an **Apply payment** workspace that uses Invoice's existing settlement-eligible query and Payment's allocation command. Selection remains constrained by tenant/organization scope, opposite Invoice direction, same party, same currency, open balance, payment unapplied balance, and optimistic row version.

Posted payment instruments now expose a **Settle instruments** workspace. The backend remains authoritative for allowed next states. Every transition supplies:

- expected Payment version;
- expected Payment Line version;
- selected next state;
- explicit business event date;
- optional reason/reference note.

An event date before the Payment date is rejected. Deposit/realization/clearing/bounce dates use the supplied business event date rather than server-local “today”.

The existing historical/reportable `returned` instrument state was reviewed. No source evidence establishes which live Payment states should transition into it, so this release does not invent a transition path. The UI exposes only transitions authorized by Payment's existing state machine.

The Payment create screen no longer advertises direct Refund or Manual creation paths whose backend contracts require controlled original-payment lineage or posting metadata. Refund remains an explicit action on an eligible Payment.

Rental security-deposit entry continues to delegate money movement to Payment and now consumes the Payment-owned usable-method lookup rather than Payment Method setup permissions.

## Reporting and business time

Consolidated reporting converts monetary values using the frozen rate owned by the source document before aggregation and uses exact `DecimalMath` rather than binary floating-point conversion:

- Invoice revenue/aging uses the Invoice exchange rate;
- realized receipts/payments use the Payment exchange rate;
- sales-settlement open credit uses the Invoice rate;
- realized allocated receipt shares use the Payment rate;
- Finance ledger/cash-flow/budget/bank-reconciliation paths use base ledger values.

Invoice and Payment transaction registers retain the original transaction amount, currency, and exchange rate. They no longer publish mathematically invalid totals across mixed currencies unless a base-value column is explicitly available.

The Configuration-owned `localization.timezone` key is centralized in `ConfigurationKey::WORKSPACE_TIMEZONE` and reused by Rental and Reporting. Dashboard “today” and generic AR/AP aging now resolve tenant/organization business time instead of process-local midnight.

Supplier-payables dashboard actions now drill into the canonical inbound Invoice population, which includes Rental owner payables, rather than a GRN-only report.

## Relationship and ownership review

No Rental relationship was changed.

The retained relationships remain directional and responsibility-owned:

- successor Agreement → predecessor Agreement;
- Vehicle Use → Customer Agreement;
- Vehicle Use → optional Owner Agreement/source;
- Vehicle Use → Vehicle;
- replacement Vehicle Use → predecessor Vehicle Use;
- Running Chart → Vehicle Use;
- corrected Running Chart → reversed/corrected predecessor;
- Rental source charge → owner-module financial document through source allocation.

No redundant inverse pointer, circular Rental dependency, duplicate mutable Invoice/Payment status, second Rental financial ledger, or compatibility bridge was added.

## External standards boundary

Official IAS 21 guidance was rechecked for the accounting boundary: foreign-currency transactions are initially recognized in the functional currency using the spot exchange rate at the transaction date. This supports freezing a document/payment transaction-date rate and keeping functional/base-currency accounting amounts distinct from transaction-currency evidence.

Sri Lanka Inland Revenue Department material was rechecked only to confirm that VAT/WHT rules are effective-dated and can change. Tax applicability, rates, thresholds, withholding, and posting remain Tax/Invoice/Payment responsibilities. No statutory percentage or Rental tax assumption is hardcoded by this release.

External research is architecture rationale only. It does not replace TACGL/video evidence or invent a Rental commercial tariff.

## Regression coverage added or updated

Focused source coverage now includes:

- foreign-currency Finance journal preserves transaction amount while base ledger/account balances/trial balance/cash flow/budget actuals use the frozen rate;
- tenant-base-currency Finance journal rejects a non-unit exchange rate;
- foreign-currency bank statement matches the base ledger amount, not the transaction amount;
- tenant-base-currency Invoice rejects a non-unit exchange rate;
- Payment instrument settlement records the explicit historical event date and optimistic versions;
- Payment instrument settlement rejects an event before the Payment date without mutation;
- sales-settlement reporting uses Invoice FX for open credit and Payment FX for realized receipts;
- Invoice settlement frontend requires explicit Payment FX and carries it in the Payment payload;
- Rental deposit frontend uses the least-privilege Payment-owned usable-method lookup.

## Verification boundary

Verification claims in this record are limited to work actually executed.

Verified during this release:

- authoritative `worktree-0.0.8` was re-read before implementation and remained at `70d6fe4f559897a6190eb913cb283c04e83658fe` through the final pre-documentation branch check;
- there were no competing open pull requests at that check;
- TACGL normalized payload equality and video/source hashes remain the registered audited set;
- protected-backup credential search remained non-destructive and found no explicit password;
- both new Finance upgrade migration files were materialized locally and passed PHP `php -l` syntax validation under PHP 8.4;
- the new Payment allocation and instrument-settlement TSX components were materialized and passed focused TypeScript transpile syntax checks;
- final source/diff review was performed against the authoritative branch;
- no paid tool and no GitHub Actions execution was used.

A fresh dependency-backed full Laravel/Vitest/typecheck/ESLint/Vite/migration execution cannot be claimed from this environment: normal Git checkout remains blocked because the runtime cannot resolve `github.com`, and the repository dependency tree is not locally available. The previously recorded full-suite and SQLite/MariaDB results in `docs/knowledgebase.md` remain historical baselines, not results for this delta.

This limitation does not justify pretending unexecuted tests passed. The release includes focused regression source coverage and local syntax checks; deployment should still run the repository's normal dependency-backed verification commands in an environment with the checkout/dependencies available.

## Release conclusion

The Vehicle Rental domain remains a fresh implementation with its acceptance ledger closed. This release completes the remaining evidence-backed end-to-end financial handoff foundation in the modules that own it:

- Rental owns Rental commercial/operational evidence;
- Invoice owns receivable/payable documents;
- Payment owns receipts/payments, allocations, refunds, and instrument settlement;
- Finance owns functional/base-currency ledger truth and reconciliation;
- Tax owns effective-dated tax/withholding rules;
- Reporting consumes those owner-module facts without mixed-currency arithmetic.

No old Rental code was restored. No legacy design flaw was preserved for compatibility. No unsupported Rental monetary rule, tax rate, GL account, password, or relationship was invented.
