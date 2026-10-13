# Purchase financial currency provenance and allocation safety — 2026-10-13

## Release blocker and factual basis

Purchase debit notes previously stored amounts without a currency identifier. The Purchase allocation service verified tenant, organization, supplier and document direction/status but sent raw note amounts to Invoice balances without a currency check. This permits cross-currency credit allocation and is the subject of GitHub issue #128.

## Purchase-owned correction on the integration candidate

- **Fresh and upgrade schema:** `purchase_debit_notes.currency_id` and `purchase_returns.currency_id` are nullable columns in their respective canonical create-table migrations, with two single-table additive `Database/UpgradeMigrations` files. Existing financial rows and amounts are never rewritten, and the upgrades do not assume a tenant default currency.
- **Manual debit notes:** creation now requires a selected active global currency, validated by Purchase, stored with the note and returned in API resources. The creation form uses the existing searchable CurrencyLookupSelect control. The selected currency is immutable after creation.
- **Referenced returns:** currency is derived from the original linked Purchase Order through the selected Goods Receipt; no currency is fabricated when the chain is incomplete. The Purchase Return stores the resulting currency snapshot. Client-provided currency is forbidden on the referenced-return API.
- **Manual supplier returns:** their original cost-basis currency is explicitly selected and validated at return creation, stored in the return and carried into any automatic debit note. Their UI uses the same CurrencyLookupSelect. Source return currency cannot be changed after creation.
- **Purchase debit notes issued from returns:** inherit the return's immutable snapshot; when a historical return has no snapshot, only an intact, scope-validated Goods Receipt → Purchase Order may provide verifiable currency. If neither applies, the note is flagged unresolved.
- **Allocation gate:** currency is checked inside the existing transaction and after tenant/org/supplier/direction/status checks, **before** Invoice-owned `allocateCredit` or Purchase note balance mutations. Either unknown currency or differing currency IDs fails explicitly. Cross-currency conversion is **not** invented. Source notes lacking currency are returned with disabled `can_allocate` capability and an actionable reason.
- **UI/API:** debit-note and return resources expose currency with human-readable lookup details. The detail UI does not expose raw database IDs and hides ineligible Allocate actions.

## Added/extended regressions

- Manual debit-note API requires active currency and returns its verified ID.
- Manual supplier-return API fixtures now pass a valid explicit currency.
- Purchase engine test records both invoice and manual note currency, allows same-currency partial/full allocation, and rejects unresolved historical-note and mismatched foreign-currency attempts without changing Invoice balance or note remainder.
- Purchase Return → Goods Receipt → Purchase Order auto-issued debit note persists the provable source currency; persisted debit-note currency mutation is rejected.
- Existing tests retain tenant/org, supplier type, document direction/status and duplicate-return integrity protections.

## Existing data and release limitations

Both upgrade columns intentionally default to **NULL for existing records**. No automatic currency backfill, guessed FX rate, destructive financial merge or historical amount conversion is authorized. Old debit notes and returns lacking documented currency require a separate reviewed reconciliation/backfill workflow using immutable source evidence. In particular, **previous cross-currency allocations, if any, are not retroactively repaired** by this guard and must be audited with Finance and Invoice owners before production release.

Review at least the following on an isolated copy of real production-like data:

```sql
SELECT tenant_id, COUNT(*) AS unresolved_notes
FROM purchase_debit_notes
WHERE currency_id IS NULL
GROUP BY tenant_id;
```

An unresolved note must not be settled by choosing the currency of whichever Invoice happens to be selected. Audit each linked source and already-issued allocation with the Finance/Invoice source of truth.

**Verification boundary:** These commits were pushed directly to the draft integration candidate. The user's clean exact-SHA local tests (920 Laravel tests, 10,092 assertions; 432 Vitest) passed on the **earlier** `2cbd11e` head and **do not verify this newer code**. Run full SQLite/MySQL suites, exact-head typecheck/lint/Vitest/build, fresh and populated upgrade/rollback, finance reconciliation, concurrency, authorization and UAT. GitHub Actions currently fails before runner startup (issue #124). Do not merge #127, close #128, or mark Vehicle Rental TODO section R satisfied until those independent requirements are met.
