# Purchase debit-note workflow and audit atomicity — 2026-10-31

## Verified defect and ownership

The Purchase debit-note HTTP controller performed financial mutations through `PurchaseDebitNoteService` and only **after the service returned** invoked `PurchaseAuditService::recordDocumentEvent()`. The service owned its own transactions, so a failure writing the required audit event could leave a newly created, approved, posted or allocated Purchase debit note **committed without the corresponding application audit record**. This is a Purchase orchestration/atomicity bug, not a problem to work around in Rental, Invoice, Finance or Audit.

The existing `RecordAuditEvent` implementation writes via `EloquentAuditLogWriter` and ordinary `AuditLog` database persistence and explicitly throws on failure. Both Purchase and Audit records use the application's database connection; a shared outer transaction can therefore roll back both the business write and its audit record.

## Implementation

Within `app/Modules/Purchase/Http/Controllers/PurchaseDebitNoteController.php`, the four HTTP mutation paths `store`, `approve`, `post` and `allocate` now enclose the existing owner-service mutation and the existing audit recorder invocation in **one `DB::transaction` boundary**. The services' nested transactions continue to enforce row locks, version checks, supplier/org/currency validation and Invoice-owned credit allocation; the outer transaction controls whether the whole HTTP operation commits.

- The regular success contract and persisted document fields remain unchanged.
- Failed audit persistence is now fail-closed for these four HTTP workflows, not reported as a partially successful operation.
- No external side effects, new financial calculations, changing currency policy, new schema or retrospective financial data mutations were introduced.
- Service-direct internal calls (for example, a Purchase return posting) are outside the scope of the debit-note HTTP controller's audit boundary; this change does not claim those internal flows are newly audited. No blanket claim of audit atomicity for every Purchase controller is made.

## Regression coverage

Two real API regression tests were added to the existing `PurchaseOrderApiTest` fixture:

1. Inject a failing `AuditRecorderInterface` after valid authorization and supplier/currency setup; a manual debit note POST must throw and leave **zero** persisted notes.
2. Create a valid audited manual note, then inject an audit failure on approve; the note must remain **draft**, its `row_version` unchanged and `approved_at` NULL.

These tests exercise the existing authorization, request, service, transaction and audit DI boundaries and will fail on the prior implementation. They do not mock away Purchase business logic.

**Actually executed in this restricted environment:** PHP 8.4.24 syntax check on a standalone reproduction of the four new controller transaction closures passed (`php -l`). The full actual controller/test files and complete Laravel suite could **not** be linted or executed here: GitHub contents access is via the connector, the available container cannot resolve github.com for a checkout and lacks Composer, database extensions and a Laravel vendor tree. The standalone check is **not** the Laravel regression test and is not a claim about an exact release head. Prior user-executed 958 Laravel / 468 frontend tests predate this change.

## Release boundary

No GitHub Actions, paid tools, rule bypass, production DB access, undocumented Finance posting or release-branch merge were performed. The outstanding unresolved historical Purchase currency/GL issue #128, hosted check issue #124, PR #127 independent review, and Rental TODO §R remain separate acceptance requirements. Full code-base tests and exact-SHA dual-database verification are required before the change can be promoted to production.
